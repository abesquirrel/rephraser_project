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

def load_knowledge_base():
    global faiss_index, knowledge_texts
    logger.info("Building Knowledge Base from Database...")
    start_time = time.time()
    embeddings = []
    texts = []
    
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT original_text, rephrased_text, keywords, is_template, category FROM knowledge_bases")
        rows = cursor.fetchall()
        cursor.close()
        conn.close()

        if rows:
            originals = [r[0] for r in rows]
            texts = [{"rephrased": r[1], "keywords": r[2], "is_template": bool(r[3]), "category": r[4]} for r in rows]
            embeddings = embedding_model.encode(originals)
    except Exception as e:
        logger.error(f"DB Load failed: {e}")

    if len(embeddings) > 0:
        d = embeddings.shape[1]
        # Lightweight index as requested (IndexFlatL2)
        index = faiss.IndexFlatL2(d)
        index.add(np.array(embeddings).astype('float32'))
        with lock:
            faiss_index = index
            knowledge_texts = texts
        
        logger.info(f"KB Built in {time.time() - start_time:.2f}s. Entries: {len(knowledge_texts)}. Dimensions: {d}. Index: IndexFlatL2")
    else:
        with lock:
            faiss_index = faiss.IndexFlatL2(384) # Default dimension for MiniLM
            knowledge_texts = []
        logger.info(f"KB Empty. Initialized empty index.")

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
    for idx_val in I[0]:
        if 0 <= idx_val < len(knowledge_texts):
            item = knowledge_texts[idx_val]
            candidates.append(item)
    
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
    return jsonify({'results': final_results})

if __name__ == '__main__':
    logger.info("Starting AI Embedding Service (Port 5002)...")
    # Initialize Model
    embedding_model = SentenceTransformer('all-MiniLM-L6-v2')
    
    # Initial load
    rebuild_worker()
            
    app.run(host='0.0.0.0', port=5002, threaded=True)
