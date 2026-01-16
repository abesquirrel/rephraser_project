import os
import sys
import logging
import json
import threading
import time
import numpy as np
import faiss
import mysql.connector
from flask import Flask, request, jsonify
from flask_cors import CORS
from sentence_transformers import SentenceTransformer

# --- Configuration ---
DB_HOST = os.environ.get('DB_HOST', 'db')
DB_USER = os.environ.get('DB_USER', 'rephraser')
DB_PASSWORD = os.environ.get('DB_PASSWORD', 'secret')
DB_NAME = os.environ.get('DB_NAME', 'rephraser_db')
AI_SERVICE_KEY = os.environ.get('AI_SERVICE_KEY', 'default_secret_key')

# Logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - [%(name)s] - %(message)s')
logger = logging.getLogger("AI_EMBEDDING")

app = Flask(__name__)
CORS(app)

@app.before_request
def verify_api_key():
    if request.path in ['/health']:
        return None
    
    key = request.headers.get('X-AI-KEY')
    if not key or key != AI_SERVICE_KEY:
        logger.warning(f"Unauthorized access attempt from {request.remote_addr}")
        return jsonify({"error": "Unauthorized"}), 401

# Global Resources
embedding_model = None
faiss_index = None
knowledge_texts = []  # List of dicts
lock = threading.Lock()

def get_db_connection():
    return mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME
    )

def update_stats_worker(ids):
    """Background worker to update usage stats."""
    if not ids:
        return
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        # Update hits and last_used_at
        format_strings = ','.join(['%s'] * len(ids))
        cursor.execute(f"UPDATE knowledge_bases SET hits = hits + 1, last_used_at = NOW() WHERE id IN ({format_strings})", tuple(ids))
        conn.commit()
        cursor.close()
        conn.close()
    except Exception as e:
        logger.error(f"Failed to update stats: {e}")

def load_knowledge_base():
    global faiss_index, knowledge_texts
    logger.info("Building Knowledge Base from Database...")
    start_time = time.time()
    embeddings = []
    texts = []
    ids_to_update = []
    new_embeddings = []
    
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        # Fetch ID and Embedding blob as well
        cursor.execute("SELECT id, original_text, rephrased_text, keywords, is_template, category, embedding FROM knowledge_bases")
        rows = cursor.fetchall()
        
        # Lists to hold data
        valid_embeddings = []
        texts_to_encode = []
        indices_to_encode = [] # Map back to original row index
        
        # Process rows
        for i, r in enumerate(rows):
            row_id = r[0]
            original = r[1]
            # Store metadata
            texts.append({
                "id": row_id,
                "rephrased": r[2], 
                "keywords": r[3], 
                "is_template": bool(r[4]), 
                "category": r[5]
            })
            
            emb_blob = r[6]
            if emb_blob:
                # Cache Hit
                try:
                    # decoding bytes to numpy array
                    emb_array = np.frombuffer(emb_blob, dtype='float32')
                    valid_embeddings.append(emb_array)
                except Exception as e:
                    logger.error(f"Error decoding blob for ID {row_id}: {e}")
                    texts_to_encode.append(original)
                    indices_to_encode.append(i)
            else:
                # Cache Miss
                texts_to_encode.append(original)
                indices_to_encode.append(i)

        # Batch encode missing
        if texts_to_encode:
            logger.info(f"Encoding {len(texts_to_encode)} new entries...")
            encoded = embedding_model.encode(texts_to_encode)
            
            # Prepare updates for DB
            for j, emb in enumerate(encoded):
                valid_embeddings.insert(indices_to_encode[j], emb) # Insert back in correct order to match texts
                row_id = texts[indices_to_encode[j]]['id']
                ids_to_update.append((emb.tobytes(), row_id))

        # Bulk update DB with new embeddings
        if ids_to_update:
            logger.info(f"Updating {len(ids_to_update)} cache entries in DB...")
            cursor.executemany("UPDATE knowledge_bases SET embedding = %s WHERE id = %s", ids_to_update)
            conn.commit()
        
        cursor.close()
        conn.close()

        if valid_embeddings:
            embeddings = np.array(valid_embeddings).astype('float32')
            d = embeddings.shape[1]
            index = faiss.IndexFlatL2(d)
            index.add(embeddings)
            with lock:
                faiss_index = index
                knowledge_texts = texts
            logger.info(f"KB Built in {time.time() - start_time:.2f}s. Entries: {len(knowledge_texts)}. New Encoded: {len(texts_to_encode)}.")
        else:
            with lock:
                faiss_index = faiss.IndexFlatL2(384)
                knowledge_texts = []
            logger.info(f"KB Empty.")

    except Exception as e:
        logger.error(f"DB Load failed: {e}")
        import traceback
        traceback.print_exc()

def rebuild_worker():
    thread = threading.Thread(target=load_knowledge_base)
    thread.daemon = True
    thread.start()

@app.route('/health', methods=['GET'])
def health_check():
    db_status = "healthy"
    try:
        conn = get_db_connection()
        conn.ping(reconnect=True)
        conn.close()
    except Exception as e:
        logger.error(f"Health check DB failure: {e}")
        db_status = "unhealthy"
    
    return jsonify({
        'status': 'healthy' if db_status == "healthy" else "degraded",
        'service': 'ai-embedding',
        'database': db_status,
        'kb_size': len(knowledge_texts)
    })

@app.route('/trigger_rebuild', methods=['POST'])
def trigger_rebuild():
    rebuild_worker()
    return jsonify({'status': 'rebuild_started'})

@app.route('/cleanup', methods=['POST'])
def cleanup_kb():
    """Prunes unused KB entries."""
    threshold_hits = request.json.get('threshold_hits', 5) # Delete if hits < 5 (example default, usually explicit)
    # Safer default: Only delete if explicitly requested
    if 'threshold_hits' not in request.json:
         return jsonify({'error': 'Missing threshold_hits parameter'}), 400
         
    deleted_count = 0
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        # Only delete files older than 7 days to avoid deleting new entries that haven't had a chance to be used
        cursor.execute("DELETE FROM knowledge_bases WHERE hits < %s AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)", (threshold_hits,))
        deleted_count = cursor.rowcount
        conn.commit()
        cursor.close()
        conn.close()
        
        if deleted_count > 0:
            rebuild_worker()
            
    except Exception as e:
        return jsonify({'error': str(e)}), 500
        
    return jsonify({'status': 'success', 'deleted': deleted_count})

@app.route('/retrieve', methods=['POST'])
def retrieve():
    data = request.json
    query_text = data.get('text', '')
    k = data.get('k', 3)
    prefer_templates = data.get('prefer_templates', False)
    category = data.get('category', None)

    if faiss_index is None or faiss_index.ntotal == 0:
        return jsonify({'results': []})
    
    # Logic copied from original retrieve_examples
    search_k = min(50, len(knowledge_texts)) if (prefer_templates or category) else k
    
    query_vector = embedding_model.encode([query_text]).astype('float32')
    D, I = faiss_index.search(query_vector, search_k)
    
    candidates = []
    found_ids = []
    
    for idx_val in I[0]:
        if 0 <= idx_val < len(knowledge_texts):
            item = knowledge_texts[idx_val]
            candidates.append(item)
            found_ids.append(item['id'])
    
    # Ranking logic
    def rank_score(item):
        score = 0
        if prefer_templates and item.get('is_template'):
            score += 10
        if category and item.get('category') == category:
            score += 20
        return score

    if prefer_templates or category:
        candidates.sort(key=rank_score, reverse=True)
        
    final_results = candidates[:k]
    
    # Update Stats for the actual returned results (or strictly hits on search? search hit seems better)
    # Let's count hits on everything FAISS found relevant enough to be in top K search
    if found_ids:
        threading.Thread(target=update_stats_worker, args=(found_ids,)).start()

    return jsonify({'results': final_results})

if __name__ == '__main__':
    logger.info("Starting AI Embedding Service (Port 5002)...")
    # Initialize Model
    embedding_model = SentenceTransformer('all-MiniLM-L6-v2')
    
    # Initial load
    rebuild_worker()
            
    app.run(host='0.0.0.0', port=5002, threaded=True)
