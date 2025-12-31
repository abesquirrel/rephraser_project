import faiss
import numpy as np
import pickle
import requests
import json
import sys
import csv
import threading
import logging
import re
import time
import io # Added to resolve NameError
from flask import Flask, request, jsonify, Response, stream_with_context
from sentence_transformers import SentenceTransformer
from ddgs import DDGS # UPDATED: Use the new ddgs library
from core import find_similar_examples, call_llm
from config import (
    OLLAMA_API_URL,
    OLLAMA_MODEL,
    FAISS_INDEX_PATH,
    REPHRASED_TEXTS_PATH,
    KNOWLEDGE_BASE_CSV,
    LOG_FILE,
    TOP_K_EXAMPLES,
    WEB_SEARCH_RESULT_COUNT,
    MAX_GENERATION_TOKENS,
)

# --- Initialize Flask App ---
app = Flask(__name__)

embedding_model = SentenceTransformer('all-MiniLM-L6-v2')

# --- SETUP LOGGING ---
# Disable Werkzeug's default logger to reduce redundant output
werkzeug_logger = logging.getLogger('werkzeug')
werkzeug_logger.setLevel(logging.ERROR)

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - [APP] - %(message)s',
    handlers=[
        logging.FileHandler(LOG_FILE),
        logging.StreamHandler()
    ]
)
csv_lock = threading.Lock()
kb_rebuild_lock = threading.Lock() # New lock for knowledge base rebuild

def rebuild_knowledge_base():
    """
    Reads the knowledge base CSV, generates embeddings, and
    creates/updates the FAISS index and rephrased texts files.
    This function should be called in a separate thread.
    """
    global embedding_model # Ensure we use the globally initialized model
    if embedding_model is None:
        logging.error("Embedding model not initialized before rebuilding knowledge base.")
        return False

    original_texts = []
    rephrased_texts_list = [] # Use a different name to avoid conflict with global

    with csv_lock: # Protect access to knowledge_base.csv
        try:
            with open(KNOWLEDGE_BASE_CSV, 'r', encoding='utf-8') as f:
                reader = csv.DictReader(f)
                for row in reader:
                    original_texts.append(row['original_text'])
                    rephrased_texts_list.append(row['rephrased_text'])
        except FileNotFoundError:
            logging.warning(f"{KNOWLEDGE_BASE_CSV} not found. Starting with an empty knowledge base.")
            return False
        except Exception as e:
            logging.error(f"Error reading {KNOWLEDGE_BASE_CSV}: {e}")
            return False

    if not original_texts:
        logging.info("No documents found in the CSV. Skipping FAISS index creation.")
        # Ensure that if the KB is empty, we still write empty files
        with open(FAISS_INDEX_PATH, 'w') as f: pass # Create empty file
        with open(REPHRASED_TEXTS_PATH, "wb") as f: pickle.dump([], f)
        return True

    logging.info(f"Generating embeddings for {len(original_texts)} documents...")
    embeddings = embedding_model.encode(original_texts, convert_to_tensor=False)
    embeddings = np.array(embeddings).astype('float32')

    d = embeddings.shape[1]
    new_index = faiss.IndexFlatL2(d)
    new_index.add(embeddings)

    faiss.write_index(new_index, FAISS_INDEX_PATH)
    with open(REPHRASED_TEXTS_PATH, "wb") as f:
        pickle.dump(rephrased_texts_list, f)

    logging.info(f"Successfully created and saved FAISS index with {new_index.ntotal} vectors.")
    return True

def _reload_kb_resources():
    """
    Reloads the global FAISS index and rephrased texts from disk.
    Ensures thread-safe access to these global variables.
    """
    global index, rephrased_texts
    with kb_rebuild_lock:
        try:
            logging.info("Reloading FAISS index and rephrased texts...")
            new_index = faiss.read_index(FAISS_INDEX_PATH)
            with open(REPHRASED_TEXTS_PATH, "rb") as f:
                new_rephrased_texts = pickle.load(f)
            index = new_index
            rephrased_texts = new_rephrased_texts
            logging.info("FAISS index and rephrased texts reloaded successfully.")
            return True
        except FileNotFoundError:
            logging.error("Knowledge base files not found during reload. Please ensure they exist.")
            # Set to empty to avoid errors
            index = faiss.IndexFlatL2(embedding_model.get_sentence_embedding_dimension())
            rephrased_texts = []
            return False
        except Exception as e:
            logging.error(f"Error reloading knowledge base resources: {e}", exc_info=True)
            return False

# Global variables for the FAISS index and rephrased texts
index = None
rephrased_texts = []

try:
    logging.info("Initial loading of resources...")
    _reload_kb_resources() # Use the new reload function for initial load
    logging.info("Initial resources loaded successfully.")
except Exception as e:
    logging.critical(f"Fatal error during initial resource loading: {e}", exc_info=True)
    sys.exit(1)
index = None
rephrased_texts = []

# --- HELPER FUNCTIONS (Updated Keyword Extraction) ---

def stream_event(status_message):
    return json.dumps({"status": status_message}) + "\n"

def extract_keywords(text):
    """Uses the LLM to extract clean search keywords from the input text."""
    logging.info("Asking LLM to extract keywords for web search...")
    # UPDATED: Prompt is more explicit about a clean, quote-free query.
    prompt = f"Extract the most important keywords for a web search from the following notes. Return only a clean, simple, quote-free search query. Do not answer the question or add any extra formatting.\n\nNotes: '{text}'"
    messages = [{"role": "user", "content": prompt}]
    keywords = call_llm(messages, temperature=0.0, max_tokens=50)
    
    # UPDATED: Sanitize the output to remove any leading/trailing quotes or whitespace.
    sanitized_keywords = keywords.strip().strip('"\'')
    
    logging.info(f"Extracted and sanitized keywords: '{sanitized_keywords}'")
    return sanitized_keywords

def perform_web_search(query):
    """Performs a web search using DDGS and returns formatted results."""
    
    # Keywords and forums for targeted search
    specific_keywords = "GMS OR cellphone service OR \"US mvno\" OR telecommunications"
    forums = "site:reddit.com OR site:howardforums.com OR site:xda-developers.com"
    
    # Construct the targeted query
    targeted_query = f"({query}) AND ({specific_keywords}) ({forums})"
    
    logging.info(f"Performing targeted web search for: '{targeted_query}'")
    
    results = []
    try:
        with DDGS() as ddgs:
            results = [r for r in ddgs.text(targeted_query, max_results=WEB_SEARCH_RESULT_COUNT)]
    except DDGSException as e:
        logging.warning(f"Targeted search raised DDGSException: {e}. Attempting broader search.")
        results = [] # Ensure results is empty if an exception occurred

    # If targeted search yields no results (either empty or exception), broaden the search
    if not results:
        broader_query = f"{query} ({forums})"
        logging.info(f"Performing broader web search for: '{broader_query}'")
        try:
            with DDGS() as ddgs:
                results = [r for r in ddgs.text(broader_query, max_results=WEB_SEARCH_RESULT_COUNT)]
        except DDGSException as e:
            logging.warning(f"Broader search raised DDGSException: {e}.")
            results = [] # Ensure results is empty if an exception occurred

    if not results:
        logging.warning("Web search returned no results, even after broadening.")
        return "No relevant information found online."
    
    formatted_results = "\n\n".join([f"Source: {r.get('href', 'N/A')}\nSnippet: {r.get('body', '')}" for r in results])
    logging.info("Web search completed successfully.")
    return formatted_results

def build_structured_prompt(original_text, examples, web_context=None, signature="Paul"):
    system_prompt = f"""You are an expert support analyst. Your task is to analyze user notes, synthesize all provided context (including web search results if available), and generate a **comprehensive, clear, and streamlined** response. Your name is {signature}.
Your goal is to convey the most relevant information concisely and with proper grammar, directly addressing the user's notes within the structured format. Avoid redundancy, overly complex phrasing, or unnecessary details. Get straight to the point in each section.

You MUST follow this template EXACTLY:
Hello,

Observations:
[Provide a clear, concise summary of the situation based on input notes and context.]

Actions Taken:
[List specific, brief actions taken or initiated.]

Recommendations:
[Offer precise, actionable next steps or advice.]

Regards,
{signature}
"""
    user_prompt = f"Here are examples of how to generate clear, structured responses:\n"
    for i, ex in enumerate(examples): user_prompt += f"\n--- Example {i+1} ---\n{ex}\n"
    if web_context: user_prompt += f"\n--- Context from Web Search ---\nI have performed a web search and found this information. Synthesize this concisely into your response where appropriate:\n{web_context}\n"
    user_prompt += f"\n--- YOUR TASK ---\nNow, using all the above information, generate a **comprehensive, concise, and streamlined** response in the required format for the following notes. Focus on clarity and relevance.\n\nInput Notes: \"{original_text}\""
    return [{"role": "system", "content": system_prompt}, {"role": "user", "content": user_prompt}]


# --- API ENDPOINTS (Unchanged) ---
@app.route('/rephrase', methods=['POST'])
def handle_rephrase():
    data = request.get_json()
    if not data or 'text' not in data:
        return Response(json.dumps({"error": "Invalid request."}))
    
    input_text = data['text']
    signature = data.get('signature', 'Paul') # Get signature, default to 'Paul'
    enable_web_search = data.get('enable_web_search', True) # Default to True if not provided

    def thinking_process_stream():
        try: # Re-added try block
            web_context = None
            if enable_web_search:
                yield stream_event("Extracting keywords for smarter search...")
                keywords = extract_keywords(input_text)
                yield stream_event(f"Searching online for: '{keywords}'...")
                web_context = perform_web_search(keywords)
            else:
                yield stream_event("Online research is disabled. Skipping web search.")
                
            yield stream_event("Searching local knowledge base for similar examples...")
            examples = find_similar_examples(input_text, embedding_model)
            yield stream_event(f"Found {len(examples)} relevant examples.")
            yield stream_event("Synthesizing all information to generate the final response...")
            messages = build_structured_prompt(input_text, examples, web_context, signature=signature)
            final_response = call_llm(messages, temperature=0.5, max_tokens=MAX_GENERATION_TOKENS)
            
            # Post-process to ensure "Regards," is always followed by signature on a new line
            # This handles cases where LLM might output "Regards, Signature" or "Regards, \nSignature"
            # It explicitly ensures the format "Regards,\nSignature"
            if f"Regards, {signature}" in final_response:
                final_response = final_response.replace(f"Regards, {signature}", f"Regards,\n{signature}")
            # Also handle cases where the LLM might have already added a newline but with extra spaces
            final_response = re.sub(r"Regards,\s*\n\s*" + re.escape(signature), f"Regards,\n{signature}", final_response, flags=re.IGNORECASE)
            final_response = final_response.strip() # Clean up leading/trailing whitespace

            yield json.dumps({"data": final_response}) + "\n"
        except Exception as e:
            logging.error(f"An error occurred during streaming process: {e}", exc_info=True)
            yield json.dumps({"error": "An internal server error occurred."}) + "\n"

    return Response(stream_with_context(thinking_process_stream()), mimetype='application/json')

def run_app(port=5001):
    # use_reloader=False is important for running in a thread
    app.run(host='0.0.0.0', port=port, use_reloader=False)

if __name__ == '__main__':
    run_app()

def _process_new_kb_entries(new_entries):
    """
    Helper function to append new entries to the CSV and trigger a background rebuild.
    """
    logging.info(f"Processing new KB entries: {new_entries}")
    if not new_entries:
        return {'status': 'error', 'error': 'No valid entries to add.'}
    
    try:
        with csv_lock:
            with open(KNOWLEDGE_BASE_CSV, 'a', newline='', encoding='utf-8') as f:
                writer = csv.writer(f)
                writer.writerows(new_entries)
        
        logging.info(f"Added {len(new_entries)} new entries to {KNOWLEDGE_BASE_CSV}.")

        def background_rebuild():
            logging.info("Starting background knowledge base rebuild...")
            if rebuild_knowledge_base():
                _reload_kb_resources()
                logging.info("Background knowledge base rebuild and reload complete.")
            else:
                logging.error("Background knowledge base rebuild failed.")
        
        rebuild_thread = threading.Thread(target=background_rebuild)
        rebuild_thread.daemon = True
        rebuild_thread.start()
        
        return {'status': 'success', 'message': f'Added {len(new_entries)} entries. Rebuilding knowledge base in background.'}
    except Exception as e:
        logging.error(f"Error processing new KB entries: {e}", exc_info=True)
        return {'status': 'error', 'error': f'Error processing new KB entries: {e}'}


@app.route('/upload_kb', methods=['POST',])
def upload_knowledge_base():
    # Check if the request contains JSON data (for single entry)
    logging.info(f"upload_knowledge_base received request. is_json: {request.is_json}")
    if request.is_json:
        data = request.get_json()
        logging.info(f"Received JSON data: {data}")
        original = data.get('original_text', '').strip()
        rephrased = data.get('rephrased_text', '').strip()
        logging.info(f"Extracted original: '{original}', rephrased: '{rephrased}'")

        if not original or not rephrased:
            logging.warning(f"JSON payload for upload_kb missing required fields. Original: '{original}', Rephrased: '{rephrased}'")
            return jsonify({'error': 'JSON must contain "original_text" and "rephrased_text" fields.'}), 400
        
        result = _process_new_kb_entries([[original, rephrased]])
        if result['status'] == 'success':
            return jsonify(result), 200
        else:
            return jsonify(result), 500
            
    # Else, assume it's a file upload
    if 'file' not in request.files:
        logging.warning("No file part in upload_kb request.")
        return jsonify({'error': 'No file part'}), 400
    
    file = request.files['file']
    if file.filename == '':
        logging.warning("No selected file for upload_kb.")
        return jsonify({'error': 'No selected file'}), 400
    
    if file and file.filename.endswith('.csv'):
        try:
            stream = io.StringIO(file.stream.read().decode("utf-8"))
            reader = csv.DictReader(stream)
            
            if 'original_text' not in reader.fieldnames or 'rephrased_text' not in reader.fieldnames:
                logging.warning("Uploaded CSV missing required headers.")
                return jsonify({'error': 'CSV must contain "original_text" and "rephrased_text" columns.'}), 400
            
            new_entries = []
            for row in reader:
                original = row.get('original_text', '').strip()
                rephrased = row.get('rephrased_text', '').strip()
                if original and rephrased: # Only add non-empty entries
                    new_entries.append([original, rephrased])
            
            if not new_entries:
                logging.warning("Uploaded CSV contains no valid entries.")
                return jsonify({'error': 'CSV contains no valid original_text and rephrased_text entries.'}), 400

            result = _process_new_kb_entries(new_entries)
            if result['status'] == 'success':
                return jsonify(result), 200
            else:
                return jsonify(result), 500
            
        except Exception as e:
            logging.error(f"Error processing uploaded CSV: {e}", exc_info=True)
            return jsonify({'error': f'Error processing CSV: {e}'}), 500
    else:
        logging.warning("Uploaded file is not a CSV or has no filename.")
        return jsonify({'error': 'Invalid file type. Please upload a CSV.'}), 400

@app.route('/approve', methods=['POST'])
def handle_approve():
    data = request.get_json()
    if not data or 'original_text' not in data or 'rephrased_text' not in data:
        logging.warning("Received invalid approve request.")
        return jsonify({'error': 'Invalid request fields.'}), 400
    try:
        with csv_lock:
            with open(KNOWLEDGE_BASE_CSV, 'a', newline='', encoding='utf-8') as f: writer = csv.writer(f); writer.writerow([data['original_text'], data['rephrased_text']])
        logging.info("Approved example saved.")
        return jsonify({'status': 'success'})
    except Exception as e:
        logging.error(f"Error saving example: {e}", exc_info=True)
        return jsonify({'error': 'Error saving example.'}), 500