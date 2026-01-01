import os
import sys
import logging
import json
import threading
import time
import requests
import re
import numpy as np
import faiss
import mysql.connector
from flask import Flask, request, jsonify, Response, stream_with_context
from flask_cors import CORS
from sentence_transformers import SentenceTransformer
from duckduckgo_search import DDGS

# --- Configuration ---
DB_HOST = os.environ.get('DB_HOST', 'db')
DB_USER = os.environ.get('DB_USER', 'rephraser')
DB_PASSWORD = os.environ.get('DB_PASSWORD', 'secret')
DB_NAME = os.environ.get('DB_NAME', 'rephraser_db')
TOP_K_EXAMPLES = 3
WEB_SEARCH_RESULT_COUNT = 3
MAX_GENERATION_TOKENS = 600

# Logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - [%(name)s] - %(message)s')
logger = logging.getLogger("AI_SERVICE")

app = Flask(__name__)
CORS(app)

# Global Resources
embedding_model = None
faiss_index = None
knowledge_texts = []  # List of rephrased texts (targets) corresponding to index
lock = threading.Lock()

# --- HELPER FUNCTIONS ---

def get_db_connection():
    return mysql.connector.connect(
        host=DB_HOST,
        user=DB_USER,
        password=DB_PASSWORD,
        database=DB_NAME
    )

def stream_event(status_message):
    return json.dumps({"status": status_message}) + "\n"

def strip_markdown(text):
    text = re.sub(r'(\*\*|__)', '', text)
    text = re.sub(r'(\*|_)', '', text)
    return text

def retrieve_examples(query_text, k=3):
    if faiss_index is None or faiss_index.ntotal == 0:
        return []
    
    query_vector = embedding_model.encode([query_text]).astype('float32')
    D, I = faiss_index.search(query_vector, k)
    
    results = []
    for idx_val in I[0]:
        if idx_val < len(knowledge_texts) and idx_val >= 0:
            results.append(knowledge_texts[idx_val])
    return results

def call_llm(messages, temperature=0.5, max_tokens=600):
    # Mock LLM for the purpose of this refactor (assuming usage of Ollama or similar externally)
    # The original code used `core.py` which called Ollama.
    # I should bring `core.py` logic inline or import it.
    # To keep this file self-contained in Docker, I'll inline a simple requests call to Ollama.
    # OLLAMA_API_URL was imported from config. assuming 'http://host.docker.internal:11434/api/chat' or similar.
    # Docker container needs to reach host Ollama.
    
    url = "http://host.docker.internal:11434/api/chat" 
    # Note: On Mac Docker, host.docker.internal works. 
    
    payload = {
        "model": os.environ.get("OLLAMA_MODEL", "llama3:8b-instruct-q3_K_M"), # Default or from config
        "messages": messages,
        "stream": False,
        "options": {"temperature": temperature, "num_predict": max_tokens}
    }
    
    try:
        r = requests.post(url, json=payload, timeout=60)
        r.raise_for_status()
        return r.json().get('message', {}).get('content', '')
    except Exception as e:
        logger.error(f"LLM Call failed: {e}")
        return "I encountered an error generating the response."

def extract_keywords(text):
    """Uses LLM to extract clean search keywords."""
    prompt = f"Extract the most important keywords for a web search from the following notes. Return only a clean, simple, quote-free search query. Do not answer the question or add any extra formatting.\n\nNotes: '{text}'"
    messages = [{"role": "user", "content": prompt}]
    keywords = call_llm(messages, temperature=0.0, max_tokens=50)
    # Strip both single and double quotes
    return keywords.strip().strip("'").strip('"')

def web_search_tool(query):
    """Performs targeted and broad web searches."""
    combined_results = []
    
    # 1. Targeted Search (Forums, Reddit, etc)
    specific_keywords = "GMS OR cellphone service OR 'US mvno' OR telecommunications"
    forums = "site:reddit.com OR site:howardforums.com OR site:xda-developers.com OR site:apple.com OR site:google.com"
    targeted_query = f"({query}) AND ({specific_keywords}) ({forums})"
    
    try:
        logger.info(f"Targeted Search: {targeted_query}")
        results = DDGS().text(targeted_query, max_results=WEB_SEARCH_RESULT_COUNT)
        if results:
            combined_results.extend([f"[Targeted] {r.get('title','')} - {r.get('body', '')}" for r in results])
    except Exception as e:
        logger.error(f"Targeted search failed: {e}")

    # 2. Broad Search (Fallback/Supplement)
    if len(combined_results) < 2:
        try:
            logger.info(f"Broad Search: {query}")
            results = DDGS().text(query, max_results=WEB_SEARCH_RESULT_COUNT)
            if results:
                combined_results.extend([f"[Broad] {r.get('title','')} - {r.get('body', '')}" for r in results])
        except Exception as e:
            logger.error(f"Broad search failed: {e}")

    if combined_results:
        return "\\n\\n".join(combined_results[:5]) # Limit total context
    return "No relevant information found online."

def build_template_prompt(original_text, signature="Paul"):
    system = f"You are {signature}. Rephrase into template:\nHello,\n\nObservations:\n...\nActions Taken:\n...\nRecommendations:\n...\nRegards,\n{signature}"
    return [{"role": "system", "content": system}, {"role": "user", "content": f"Rephrase: {original_text}"}]

def build_structured_prompt(original_text, examples, web_context=None, signature="Paul"):
    # (Simplified for brevity, but mimicking structure)
    system = f"You are {signature}. Support Analyst. Clear, concise. RETURN ONLY THE REPHRASED RESPONSE. NO PREAMBLE. NO 'Here is the response'. NO MARKDOWN unless part of the email. Structure:\nHello,\n\nObservations:\n...\nActions Taken:\n...\nRecommendations:\n...\nRegards,\n{signature}"
    user = f"Context:\n{web_context}\n\nExamples:\n{examples}\n\nNotes: {original_text}"
    return [{"role": "system", "content": system}, {"role": "user", "content": user}]


def load_knowledge_base():
    global faiss_index, knowledge_texts
    logger.info("Building Knowledge Base from Database...")
    start_time = time.time()
    embeddings = []
    texts = []
    
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT original_text, rephrased_text FROM knowledge_bases")
        rows = cursor.fetchall()
        cursor.close()
        conn.close()

        if rows:
            originals = [r[0] for r in rows]
            texts = [r[1] for r in rows]
            embeddings = embedding_model.encode(originals)
    except Exception as e:
        logger.error(f"DB Load failed: {e}")

    if len(embeddings) > 0:
        d = embeddings.shape[1]
        index = faiss.IndexFlatL2(d)
        index.add(np.array(embeddings).astype('float32'))
        with lock:
            faiss_index = index
            knowledge_texts = texts
    else:
        with lock:
            faiss_index = faiss.IndexFlatL2(384)
            knowledge_texts = []
            
    logger.info(f"KB Built in {time.time() - start_time:.2f}s. Entries: {len(knowledge_texts)}")

def rebuild_worker():
    thread = threading.Thread(target=load_knowledge_base)
    thread.daemon = True
    thread.start()

@app.route('/trigger_rebuild', methods=['POST'])
def trigger_rebuild():
    rebuild_worker()
    return jsonify({'status': 'rebuild_started'})

@app.route('/rephrase', methods=['POST'])
def handle_rephrase():
    data = request.json
    input_text = data.get('text', '')
    signature = data.get('signature', 'Paul')
    enable_web_search = data.get('enable_web_search', True)
    search_keywords = data.get('search_keywords', '')
    template_mode = data.get('template_mode', False)

    def thinking_process_stream():
        yield stream_event("Analyzing Context...")
        
        if template_mode:
            yield stream_event("Using template mode...")
            messages = build_template_prompt(input_text, signature)
        else:
            web_context = ""
            if enable_web_search:
                if search_keywords:
                    yield stream_event(f"Searching: {search_keywords}...")
                    web_context = web_search_tool(search_keywords)
                else:
                    yield stream_event("Extracting keywords...")
                    kw = extract_keywords(input_text)
                    yield stream_event(f"Searching: {kw}...")
                    web_context = web_search_tool(kw)
            
            yield stream_event("Checking Knowledge Base...")
            examples = retrieve_examples(input_text)
            yield stream_event(f"Found {len(examples)} examples.")
            
            yield stream_event("Synthesizing...")
            messages = build_structured_prompt(input_text, examples, web_context, signature)

        response = call_llm(messages)
        response = strip_markdown(response)
        
        logging.info(f"Final Response: {response[:100]}...")
        yield json.dumps({"data": response}) + "\n"

    return Response(stream_with_context(thinking_process_stream()), mimetype='application/json')

if __name__ == '__main__':
    logger.info("Starting AI Service...")
    embedding_model = SentenceTransformer('all-MiniLM-L6-v2')
    
    # Initial wait for DB
    for i in range(10):
        try:
            load_knowledge_base()
            break
        except:
            logger.info("Waiting for DB...")
            time.sleep(5)
            
    app.run(host='0.0.0.0', port=5001, threaded=True)