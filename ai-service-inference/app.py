import os
import sys
import logging
import json
import time
import re
import requests
import threading
from flask import Flask, request, jsonify, Response, stream_with_context
from flask_cors import CORS
from duckduckgo_search import DDGS

# --- Configuration ---
AI_SERVICE_KEY = os.environ.get('AI_SERVICE_KEY', 'default_secret_key')
# Embedding service URL (internal docker network)
AI_EMBEDDING_HOST = os.environ.get('AI_EMBEDDING_HOST', 'ai-embedding')
AI_EMBEDDING_URL = f"http://{AI_EMBEDDING_HOST}:5002"

TOP_K_EXAMPLES = 3
WEB_SEARCH_RESULT_COUNT = 3
MAX_GENERATION_TOKENS = 600

# Logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - [%(name)s] - %(message)s')
logger = logging.getLogger("AI_INFERENCE")

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

# --- HELPER FUNCTIONS ---

def stream_event(status_message):
    return json.dumps({"status": status_message}) + "\n"

def strip_markdown(text):
    # Remove bold/italic markers
    text = re.sub(r'(\*\*|__)', '', text)
    text = re.sub(r'(\*|_)', '', text)
    # Remove headers
    text = re.sub(r'^#+\s*', '', text, flags=re.MULTILINE)
    # Remove code blocks
    text = re.sub(r'```.*?```', '', text, flags=re.DOTALL)
    # Remove stray markdown symbols
    text = text.replace("`", "")
    return text.strip()

def retrieve_examples_remote(query_text, k=3, prefer_templates=False, category=None):
    try:
        payload = {
            "text": query_text,
            "k": k,
            "prefer_templates": prefer_templates,
            "category": category
        }
        headers = {"X-AI-KEY": AI_SERVICE_KEY}
        resp = requests.post(f"{AI_EMBEDDING_URL}/retrieve", json=payload, headers=headers, timeout=5)
        if resp.status_code == 200:
            return resp.json().get('results', [])
        else:
            logger.error(f"Embedding service returned {resp.status_code}")
            return []
    except Exception as e:
        logger.error(f"Failed to retrieve examples from embedding service: {e}")
        return []

def call_llm(messages, temperature=0.5, max_tokens=600, model=None):
    default_model = os.environ.get("OLLAMA_MODEL", "llama3:8b-instruct-q3_K_M")
    target_model = model if model else default_model
    
    # Ollama on host
    url = "http://host.docker.internal:11434/api/chat" 
    
    payload = {
        "model": target_model,
        "messages": messages,
        "stream": False,
        "keep_alive": "5m",
        "options": {"temperature": float(temperature), "num_predict": int(max_tokens)}
    }
    
    try:
        r = requests.post(url, json=payload, timeout=120) 
        if r.status_code != 200:
            logger.error(f"Ollama error {r.status_code}: {r.text}")
        r.raise_for_status()
        return r.json().get('message', {}).get('content', '')
    except Exception as e:
        logger.error(f"LLM Call failed for model {target_model}: {e}")
        return f"Error with {target_model}: Generate failed."

def call_llm_stream(messages, temperature=0.5, max_tokens=600, model=None):
    default_model = os.environ.get("OLLAMA_MODEL", "llama3:8b-instruct-q3_K_M")
    target_model = model if model else default_model
    url = "http://host.docker.internal:11434/api/chat"
    
    payload = {
        "model": target_model,
        "messages": messages,
        "stream": True,
        "keep_alive": "5m",
        "options": {"temperature": float(temperature), "num_predict": int(max_tokens)}
    }
    
    try:
        with requests.post(url, json=payload, stream=True, timeout=120) as r:
            r.raise_for_status()
            for line in r.iter_lines():
                if line:
                    chunk = json.loads(line)
                    if 'message' in chunk and 'content' in chunk['message']:
                        yield chunk['message']['content']
                    if chunk.get('done'):
                        break
    except Exception as e:
        logger.error(f"LLM Stream failed for model {target_model}: {e}")
        yield f"\n[Error with {target_model}]"

def extract_keywords(text):
    # Fast path: Extract common identifiers via Regex
    # IMEI: 15 digits, MSISDN: 10-12 digits, Error Codes: uppercase + digits
    imeis = re.findall(r'\b\d{15}\b', text)
    msisdns = re.findall(r'\b\d{10,12}\b', text)
    error_codes = re.findall(r'\b[A-Z0-9]{5,15}\b', text)
    
    technical_hits = imeis + msisdns + error_codes
    if len(technical_hits) >= 2:
        return " ".join(technical_hits[:5])

    # LLM fallback only if no clear technical markers found
    prompt = (
        "Context: Mobile Telecommunications, US MVNOs, eSIM, Device Compatibility (Apple/Google/Samsung), Network Settings.\n"
        "Task: Extract 3-5 specific technical search keywords from the notes. Focus on device models, specific error messages, or carrier names.\n"
        "Format: Return ONLY the keywords separated by spaces. No exclusionary words. No intros.\n\n"
        f"Notes: '{text}'"
    )
    messages = [{"role": "user", "content": prompt}]
    keywords = call_llm(messages, temperature=0.1, max_tokens=50)
    return keywords.strip().strip("'").strip('"')

def web_search_tool(query):
    combined_results = []
    
    # refined context as requested
    specific_keywords = "mobile service OR 'US MVNO' OR 'eSIM support' OR 'cellular network'"
    forums = "site:reddit.com OR site:apple.com OR site:google.com OR site:howardforums.com OR site:xda-developers.com"
    targeted_query = f"{query} ({specific_keywords}) ({forums})"
    
    try:
        logger.info(f"Targeted Search: {targeted_query}")
        results = DDGS().text(targeted_query, max_results=WEB_SEARCH_RESULT_COUNT)
        if results:
            combined_results.extend([f"[Source: {r.get('title','')}] {r.get('body', '')}" for r in results])
    except Exception as e:
        logger.error(f"Targeted search failed: {e}")

    if len(combined_results) < 2:
        try:
            logger.info(f"Broad Search: {query}")
            results = DDGS().text(query, max_results=WEB_SEARCH_RESULT_COUNT)
            if results:
                combined_results.extend([f"[Broad] {r.get('title','')} - {r.get('body', '')}" for r in results])
        except Exception as e:
            logger.error(f"Broad search failed: {e}")

    if combined_results:
        return "\\n\\n".join(combined_results[:5])
    return "No relevant information found online."

def build_structured_prompt(original_text, examples, web_context=None, signature="Paul", direct_instruction=None, negative_prompt=None, template_mode=False, role="tech_support", role_config=None):
    """
    Constructs the system and user messages for the LLM.
    Supports expandable 'roles' for future scalability.
    """
    
    # --- ROLE DEFINITIONS ---
    # Scalability: Add new roles here in the future
    prompts = {
        "tech_support": {
            "identity": f"You are {signature}. Technical Support Specialist.",
            "protocol": (
                "### PROTOCOL\n"
                "1. **Audience**: You are writing to a colleague or customer requiring detailed technical context.\n"
                "2. **Analyze**: Identify the core issue, actions taken, and next steps.\n"
                "3. **Format**: STICK STRICTLY to the required section headers.\n"
            ),
            "format": (
                "Hello,\n\n"
                "Observations: (Details of the issue observed, potential problems, and diagnosis)\n\n"
                "Actions taken: (Active actions performed to fix/correct/improve. Leave empty if none)\n\n"
                "Recommendations: (Suggestions for the customer, preventive measures, or expected customer actions)\n\n"
                "Regards,\n"
                "{signature}"
            )
        },
        "customer_support": {
            "identity": f"You are {signature}. Customer Support Representative.",
            "protocol": (
                "### PROTOCOL\n"
                "1. **Audience**: You are writing to an END USER. Be polite, empathetic, and clear.\n"
                "2. **Focus**: Reassure the customer and explain things simply.\n"
                "3. **Format**: Standard professional letter format.\n"
            ),
            "format": (
                "Hello,\n\n"
                "(Rephrased content politely explaining the situation and resolution)\n\n"
                "Regards,\n"
                "{signature}"
            )
        },
        "general": {
            "identity": f"You are {signature}. AI Assistant.",
            "protocol": (
                "### PROTOCOL\n"
                "1. **Audience**: General audience.\n"
                "2. **Goal**: Rephrase the text clearly and professionally.\n"
            ),
            "format": (
                "{signature} says:\n\n"
                "(Rephrased content here)\n"
            )
        }
    }
    
    # Select Role
    current_role = prompts.get(role, prompts.get("tech_support"))
    
    # Override if dynamic config provided
    if role_config:
        current_role = {
            "identity": role_config.get('identity', current_role['identity']).replace('{signature}', signature),
            "protocol": role_config.get('protocol_override', current_role['protocol']),
            "format": role_config.get('format_override', current_role['format'])
        }
    
    # --- BUILD SYSTEM PROMPT ---
    system = f"{current_role['identity']} PLAIN TEXT ONLY.\n\n"
    system += current_role['protocol'] + "\n"

    shared_constraints = (
        "### FORMATTING CONSTRAINTS (CRITICAL)\n"
        "1. **NO MARKDOWN**: Do not use bold (**), italics (*), headers (###), or lists (-). Write in clean, plain paragraphs within the sections.\n"
        "2. **NO PREAMBLE**: Do not say 'Here is the response'. Start directly with 'Hello,'.\n"
        "3. **PROFESSIONAL TONE**: Concise, polite, support-oriented.\n"
        "4. **PRESERVE IDs**: Keep all IMEI, MSISDN, and specific error codes exactly as they appear.\n\n"
    )
    
    # --- MODE ADJUSTMENTS ---
    if web_context:
        system += "### MODE: WEB VERIFICATION\n"
        system += "Use 'Web Search Context' to valid claims in 'Notes'. Correct any technical inaccuracies in the 'Recommendations' section.\n\n"
    elif template_mode:
        system += "### MODE: TEMPLATE ADAPTER\n"
        system += "Use 'Reference Examples' as the structural guide, but inject the 'Notes' content into it.\n\n"
    else:
        system += "### MODE: REPHRASE\n"
        system += "Standard rephrasing mode. Focus on clarity and grammar.\n\n"

    # --- SHARED INSTRUCTIONS ---
    system += shared_constraints

    if direct_instruction:
        system += f"### USER DIRECTIVE: {direct_instruction}\n"
        system += "Follow this instruction above all else.\n\n"
    
    if negative_prompt:
        system += f"### STYLE EXCLUSIONS (NEGATIVE PROMPT)\n"
        system += f"You MUST AVOID: {negative_prompt}\n\n"
    
    # --- FINAL FORMATTING ---
    system += f"### REQUIRED OUTPUT FORMAT\n"
    system += "You must follow this structure exactly:\n"
    system += current_role['format'].format(signature=signature) + "\n\n"
    
    system += "CRITICAL: The output must start exactly with 'Hello,'."
    
    # --- BUILD USER CONTENT ---
    user = f"Notes (SOURCE DATA):\n{original_text}\n\n"
    
    if web_context:
        user += f"Web Search Context (FACT CHECKING SOURCE):\n{web_context}\n\n"
    if examples:
        user += f"Reference Examples (STRUCTURE SOURCE):\n{examples}"
    
    return [{"role": "system", "content": system}, {"role": "user", "content": user}]

# --- ROUTES ---

@app.route('/health', methods=['GET'])
def health_check():
    return jsonify({
        'status': 'healthy',
        'service': 'ai-inference',
        'embedding_service_url': AI_EMBEDDING_URL
    })

@app.route('/suggest_keywords', methods=['POST'])
def suggest_keywords():
    data = request.json
    text = data.get('text', '')
    if not text:
        return jsonify({'keywords': ''})
    
    prompt = f"List 3-5 keywords for indexing this text. Return ONLY a comma-separated list of clean, relevant tags. NO numbers, NO identifiers (IMEI/MSISDN), NO PREAMBLE, NO EXPLANATION.\n\nText: {text}"
    keywords = call_llm([{"role": "user", "content": prompt}], temperature=0.1, max_tokens=100)
    
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
    
    if "\n" in cleaned:
        items = [i.strip("- ").strip("123456789. ") for i in cleaned.split("\n") if i.strip()]
    else:
        items = [i.strip() for i in cleaned.split(",") if i.strip()]
    
    final_keywords = ", ".join(items[:5])
    return jsonify({'keywords': final_keywords})

@app.route('/list_models', methods=['GET'])
def list_models():
    try:
        url = "http://host.docker.internal:11434/api/tags"
        r = requests.get(url, timeout=5)
        if r.status_code == 200:
            data = r.json()
            models = [m.get('name') for m in data.get('models', [])]
            return jsonify({'models': models})
        else:
            return jsonify({'models': [], 'error': f"Ollama Error: {r.status_code}"})
    except Exception as e:
        logger.error(f"Failed to list models: {e}")
        return jsonify({'models': [], 'error': str(e)})

@app.route('/rephrase', methods=['POST'])
def handle_rephrase():
    data = request.json
    input_text = data.get('text', '')
    signature = data.get('signature', 'Paul')
    enable_web_search = data.get('enable_web_search', True)
    search_keywords = data.get('search_keywords', '')
    template_mode = data.get('template_mode', False)
    category = data.get('category', None)

    # Mode Enforcement Logic
    if enable_web_search:
        # Search Mode implies NOT Template Mode
        template_mode = False
    elif template_mode:
        # Template Mode implies NOT Search Mode
        enable_web_search = False
    # If both are False -> Standard Rephrase Mode
    target_model = data.get('model', None) 
    negative_prompt = data.get('negative_prompt', None)
    
    role_config = data.get('role_config', None)
    
    logger.info(f"DEBUG: Input Text: '{input_text}'")
    
    temperature = max(0.0, min(1.0, float(data.get('temperature', 0.5))))
    max_tokens = max(50, min(2000, int(data.get('max_tokens', 600))))
    kb_count = max(1, min(10, int(data.get('kb_count', TOP_K_EXAMPLES))))

    def thinking_process_stream():
        yield stream_event(f"Resource Profile: {max_tokens} tokens | {kb_count} context hits")
        overall_start = time.time()
        
        # Parallel Task Results
        results = {"web": "", "kb": []}
        
        def run_kb_search():
            t_start = time.time()
            res = retrieve_examples_remote(input_text, k=kb_count, prefer_templates=template_mode, category=category)
            results["kb"] = res
            logger.info(f"KB Retrieval took {time.time() - t_start:.3f}s")

        def run_web_search():
            if not enable_web_search: return
            t_start = time.time()
            kw = search_keywords
            if not kw:
                # Fast path keyword extraction or LLM fallback
                if len(input_text) < 100:
                    kw = input_text
                else:
                    kw = extract_keywords(input_text)
            results["web"] = web_search_tool(kw)
            logger.info(f"Web Search took {time.time() - t_start:.3f}s")

        # Start background threads
        yield stream_event("Gathering context (Parallel)...")
        t1 = threading.Thread(target=run_kb_search)
        t2 = threading.Thread(target=run_web_search)
        t1.start()
        t2.start()
        
        # Wait for both
        t1.join()
        t2.join()
        
        examples_list = results["kb"]
        web_context = results["web"]
        
        yield stream_event(f"Context Ready: {len(examples_list)} KB hits | {'Web search applied' if web_context else 'Local only'}")
        
        instruction_match = re.search(r'<(.*?)>', input_text)
        direct_instruction = instruction_match.group(1) if instruction_match else None
        
        formatted_examples = ""
        for i, ex in enumerate(examples_list):
            item_cat = f" [{ex.get('category')}]" if ex.get('category') else ""
            rephrased = ex.get('rephrased', '') if isinstance(ex, dict) else ex
            formatted_examples += f"Example {i+1}{item_cat}:\n{rephrased}\n\n"

        yield stream_event("Synthesizing...")
        
        messages = build_structured_prompt(
            input_text, 
            formatted_examples, 
            web_context, 
            signature, 
            direct_instruction, 
            negative_prompt, 
            template_mode,
            role=data.get('role', 'tech_support'),
            role_config=role_config
        )

        full_response = ""
        l_start = time.time()
        
        # Stream the tokens
        token_count = 0
        for token in call_llm_stream(messages, temperature=temperature, max_tokens=max_tokens, model=target_model):
            full_response += token
            token_count += 1
            # Yield token for frontend
            yield json.dumps({"token": token}) + "\n"
        
        logger.info(f"LLM Synthesis took {time.time() - l_start:.3f}s")
        
        total_latency = time.time() - overall_start
        kb_ids = [ex.get('id') for ex in examples_list if isinstance(ex, dict) and ex.get('id')]
        
        yield json.dumps({
            "data": full_response, # Final full text for consistency
            "meta": {
                "latency": total_latency, 
                "tokens": token_count,
                "kb_ids": kb_ids
            }
        }) + "\n"

    return Response(stream_with_context(thinking_process_stream()), mimetype='application/json')

if __name__ == '__main__':
    logger.info("Starting AI Inference Service (Port 5001)...")
    app.run(host='0.0.0.0', port=5001, threaded=True)
