import faiss
import numpy as np
import pickle
import requests
import json
import sys
from sentence_transformers import SentenceTransformer
from config import (
    FAISS_INDEX_PATH,
    REPHRASED_TEXTS_PATH,
    TOP_K_EXAMPLES,
    OLLAMA_API_URL,
    OLLAMA_MODEL,
)

try:
    index = faiss.read_index(FAISS_INDEX_PATH)
    with open(REPHRASED_TEXTS_PATH, "rb") as f:
        rephrased_texts = pickle.load(f)
except FileNotFoundError as e:
    print(f"Error: Could not load resource file. {e}")
    print("Please make sure you have run the 'create_kb.py' script first.")
    sys.exit(1)

def find_similar_examples(text, embedding_model):
    query_embedding = embedding_model.encode([text]).astype('float32')
    distances, indices = index.search(query_embedding, TOP_K_EXAMPLES)
    safe_indices = [i for i in indices[0] if i < len(rephrased_texts)]
    return [rephrased_texts[i] for i in safe_indices]

def call_llm(messages, temperature=0.0, max_tokens=None):
    # ... (Unchanged) ...
    options = {"temperature": temperature}
    if max_tokens: options["num_predict"] = max_tokens
    payload = {"model": OLLAMA_MODEL, "messages": messages, "stream": False, "options": options}
    response = requests.post(OLLAMA_API_URL, json=payload)
    response.raise_for_status()
    return response.json().get("message", {}).get("content", "").strip()

def rephrase_text_cli(messages):
    """
    Sends the chat messages to the Ollama API and returns the rephrased text for the CLI.
    """
    try:
        payload = {
            "model": OLLAMA_MODEL,
            "messages": messages,
            "stream": False,
        }
        
        print("\nSending request to Ollama via /api/chat...")
        
        response = requests.post(OLLAMA_API_URL, json=payload)
        response.raise_for_status()
        
        response_data = response.json()
        return response_data.get("message", {}).get("content", "Error: No response content from model.").strip()
        
    except requests.exceptions.RequestException as e:
        print(f"\nError connecting to Ollama API at {OLLAMA_API_URL}")
        print(f"Please ensure Ollama is running and the model '{OLLAMA_MODEL}' is available.")
        print(f"Details: {e}")
        return None