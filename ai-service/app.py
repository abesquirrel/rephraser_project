import os
import sys
import logging
import json
import threading
import time
import requests
from datetime import datetime
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
AI_SERVICE_KEY = os.environ.get('AI_SERVICE_KEY', 'default_secret_key')

# Logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - [%(name)s] - %(message)s')
logger = logging.getLogger("AI_SERVICE")

app = Flask(__name__)
CORS(app)

@app.before_request
def verify_api_key():
    if request.path in ['/health', '/docs', '/swagger.json']:
        return None
    
    key = request.headers.get('X-AI-KEY')
    if not key or key != AI_SERVICE_KEY:
        logger.warning(f"Unauthorized access attempt from {request.remote_addr}")
        return jsonify({"error": "Unauthorized"}), 401

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

def retrieve_examples(query_text, k=3, prefer_templates=False, category=None):
    if faiss_index is None or faiss_index.ntotal == 0:
        return []
    
    # If a category is provided, we still search top candidates but filter them
    # To ensure we get results even with a filter, we search a larger pool if filtered
    search_k = min(50, len(knowledge_texts)) if (prefer_templates or category) else k
    
    query_vector = embedding_model.encode([query_text]).astype('float32')
    D, I = faiss_index.search(query_vector, search_k)
    
    candidates = []
    for idx_val in I[0]:
        if 0 <= idx_val < len(knowledge_texts):
            item = knowledge_texts[idx_val]
            # Filter by category if strictly requested (soft search: preference given to matches)
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
        
    return candidates[:k]

def call_llm(messages, temperature=0.5, max_tokens=600, model=None):
    # Use provided model or default
    default_model = os.environ.get("OLLAMA_MODEL", "llama3:8b-instruct-q3_K_M")
    target_model = model if model else default_model
    
    url = "http://host.docker.internal:11434/api/chat" 
    
    payload = {
        "model": target_model,
        "messages": messages,
        "stream": False,
        "options": {"temperature": float(temperature), "num_predict": int(max_tokens)}
    }
    
    try:
        r = requests.post(url, json=payload, timeout=60)
        if r.status_code != 200:
            logger.error(f"Ollama error {r.status_code}: {r.text}")
        r.raise_for_status()
        return r.json().get('message', {}).get('content', '')
    except Exception as e:
        logger.error(f"LLM Call failed for model {target_model}: {e}")
        return f"Error with {target_model}: Generate failed."

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

def build_structured_prompt(original_text, examples, web_context=None, signature="Paul", direct_instruction=None, negative_prompt=None):
    # System prompt defining role and default structure
    system = f"You are {signature}. Support Analyst. Clear, concise. RETURN ONLY THE REPHRASED RESPONSE.\n\n"
    
    system += "### MANDATORY DATA REQUIRMENT\n"
    system += "You MUST extract and repeat all specific numerical identifiers (MSISDN, IMEI, ICCID, Serial Numbers) from the 'Notes' section below. "
    system += "Place these identifiers literally in the 'Observations' section of your response. DO NOT redact, change, or omit these numbers. "
    system += "If the notes say 'IMEI 12345', your response MUST say 'IMEI 12345'.\n\n"

    if direct_instruction:
        system += f"USER DIRECTIVE: {direct_instruction}\n"
        system += "Apply this directive while strictly maintaining the mandatory data requirement above.\n\n"
    
    if negative_prompt:
        system += f"STYLE EXCLUSIONS (AVOID): {negative_prompt}\n\n"
    
    system += f"Format:\nHello,\n\nObservations:\n(List facts and ALL literal identifiers here)\n\nActions Taken:\n...\nRecommendations:\n...\n\nRegards,\n{signature}"
    
    user = f"Context (General Knowledge):\n{web_context}\n\nExamples (Style Reference):\n{examples}\n\nNotes (SPECIFIC SOURCE DATA - PRESERVE ALL NUMBERS):\n{original_text}"
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

@app.route('/swagger.json')
def swagger_spec():
    """Generates the OpenAPI specification for the AI service."""
    return jsonify({
        "openapi": "3.0.0",
        "info": {
            "title": "Rephraser AI Service API",
            "version": "1.0.0",
            "description": "Standardized microservice for knowledge-aware text rephrasing."
        },
        "paths": {
            "/rephrase": {
                "post": {
                    "summary": "Process text for rephrasing",
                    "description": "Generates a rephrased response using KB context and optional web search.",
                    "responses": {
                        "200": {"description": "Dynamic JSON stream with progress events and final data."}
                    }
                }
            },
            "/trigger_rebuild": {
                "post": {
                    "summary": "Manual KB Rebuild",
                    "description": "Triggers an asynchronous rebuild of the FAISS index from the database."
                }
            },
            "/health": {
                "get": {
                    "summary": "Service Health",
                    "description": "Returns system status and connection health."
                }
            }
        }
    })

@app.route('/docs')
def get_docs():
    """Serves the interactive Swagger UI."""
    return """
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Rephraser AI Docs</title>
        <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.11.0/swagger-ui.css" />
        <style>html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; } *, *:before, *:after { box-sizing: inherit; } body { margin:0; background: #fafafa; }</style>
    </head>
    <body style="background: white;">
        <div id="swagger-ui"></div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/swagger-ui/5.11.0/swagger-ui-bundle.js"></script>
        <script>
            window.onload = () => {
                const ui = SwaggerUIBundle({
                    url: '/swagger.json',
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [SwaggerUIBundle.presets.apis],
                });
            };
        </script>
    </body>
    </html>
    """

@app.route('/')
def index_redirect():
    """Redirect root to documentation."""
    return get_docs()

@app.route('/health', methods=['GET'])
def health_check():
    """Service health check including database connectivity."""
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
        'database': db_status,
        'kb_size': len(knowledge_texts),
        'uptime_records': True # Service is running
    })

@app.route('/trigger_rebuild', methods=['POST'])
def trigger_rebuild():
    rebuild_worker()
    return jsonify({'status': 'rebuild_started'})

@app.route('/suggest_keywords', methods=['POST'])
def suggest_keywords():
    """Predicts relevant keywords for a given text."""
    data = request.json
    text = data.get('text', '')
    if not text:
        return jsonify({'keywords': ''})
    
    prompt = f"List 3-5 keywords for indexing this text. Return ONLY a comma-separated list of clean, relevant tags. NO numbers, NO identifiers (IMEI/MSISDN), NO PREAMBLE, NO EXPLANATION.\n\nText: {text}"
    keywords = call_llm([{"role": "user", "content": prompt}], temperature=0.1, max_tokens=100)
    
    # Strict list extraction
    # Remove common conversational preambles more aggressively
    preamble_patterns = [
        r'^[^{}\w]*(the\s+)?keywords(\s+are|\s+suggested)?\s*:?\s*',
        r'^Here\s+are\s+.*keywords.*:\s*',
        r'^Suggested\s+keywords:\s*',
        r'^Comma-separated\s+keywords:\s*',
        r'^The\s+relevant\s+keywords\s+are:\s*'
    ]
    cleaned = keywords
    for pattern in preamble_patterns:
        cleaned = re.sub(pattern, '', cleaned, flags=re.IGNORECASE)
    
    cleaned = cleaned.strip().strip('\"').strip("'").strip(".")
    
    # Split into list to filter and merge
    if "\n" in cleaned:
        items = [i.strip("- ").strip("123456789. ") for i in cleaned.split("\n") if i.strip()]
    else:
        items = [i.strip() for i in cleaned.split(",") if i.strip()]
    
    # Limit to top items
    final_keywords = ", ".join(items[:5])
        
    return jsonify({'keywords': final_keywords})

@app.route('/rephrase', methods=['POST'])
def handle_rephrase():
    data = request.json
    input_text = data.get('text', '')
    signature = data.get('signature', 'Paul')
    enable_web_search = data.get('enable_web_search', True)
    search_keywords = data.get('search_keywords', '')
    template_mode = data.get('template_mode', False)
    category = data.get('category', None)
    target_model = data.get('model', None) # Support for model switching/AB testing
    negative_prompt = data.get('negative_prompt', None)
    
    # Tuning Parameters (with Safe Limits for 16GB RAM)
    temperature = max(0.0, min(1.0, float(data.get('temperature', 0.5))))
    max_tokens = max(50, min(2000, int(data.get('max_tokens', 600))))
    kb_count = max(1, min(10, int(data.get('kb_count', TOP_K_EXAMPLES))))

    def thinking_process_stream():
        yield stream_event(f"Resource Profile: {max_tokens} tokens | {kb_count} context hits")
        overall_start = time.time()
        
        web_context = ""
        examples_list = []

        if template_mode:
            yield stream_event("Searching Prioritized Templates...")
            t_start = time.time()
            examples_list = retrieve_examples(input_text, k=kb_count, prefer_templates=True, category=category)
            logger.info(f"KB Retrieval took {time.time() - t_start:.3f}s")
            yield stream_event(f"Found {len(examples_list)} relevant patterns.")
        else:
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
            t_start = time.time()
            examples_list = retrieve_examples(input_text, k=kb_count, category=category)
            logger.info(f"KB Retrieval took {time.time() - t_start:.3f}s")
            yield stream_event(f"Found {len(examples_list)} examples.")
        
        # Feature: Support for bracketed instructions <rephrase as formal email>
        instruction_match = re.search(r'<(.*?)>', input_text)
        direct_instruction = instruction_match.group(1) if instruction_match else None
        
        if direct_instruction:
            yield stream_event(f"Applying Instruction: {direct_instruction[:30]}...")
            # Clean instruction from text to avoid redundancy if desired, or keep for context
            # Let's keep it in the text but flag it specifically
        
        # Format examples for prompt
        formatted_examples = ""
        for i, ex in enumerate(examples_list):
            item_cat = f" [{ex.get('category')}]" if ex.get('category') else ""
            rephrased = ex.get('rephrased', '') if isinstance(ex, dict) else ex
            formatted_examples += f"Example {i+1}{item_cat}:\n{rephrased}\n\n"

        yield stream_event("Synthesizing...")
        messages = build_structured_prompt(input_text, formatted_examples, web_context, signature, direct_instruction, negative_prompt)

        l_start = time.time()
        response = call_llm(messages, temperature=temperature, max_tokens=max_tokens, model=target_model)
        logger.info(f"LLM Synthesis took {time.time() - l_start:.3f}s")
        response = strip_markdown(response)
        
        total_latency = time.time() - overall_start
        char_count = len(response)
        # Rough token estimation (1 token ~ 4 chars)
        est_tokens = char_count // 4
        
        logger.info(f"Final Response ({char_count} chars, ~{est_tokens} tokens). Total Latency: {total_latency:.2f}s")
        yield json.dumps({"data": response, "meta": {"latency": total_latency, "tokens": est_tokens}}) + "\n"

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