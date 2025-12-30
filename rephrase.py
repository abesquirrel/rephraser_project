import faiss
import numpy as np
import pickle
import requests
import json
import sys
from sentence_transformers import SentenceTransformer

# --- CONFIGURATION ---
# Use the /api/chat endpoint, which is more standard in recent Ollama versions.
OLLAMA_API_URL = "http://localhost:11434/api/chat"
OLLAMA_MODEL = "phi3:mini" # Corrected model name
FAISS_INDEX_PATH = "faiss_index.bin"
REPHRASED_TEXTS_PATH = "rephrased_texts.pkl"
TOP_K_EXAMPLES = 3

# --- LOAD RESOURCES ---

try:
    index = faiss.read_index(FAISS_INDEX_PATH)
    with open(REPHRASED_TEXTS_PATH, "rb") as f:
        rephrased_texts = pickle.load(f)
    embedding_model = SentenceTransformer('all-MiniLM-L6-v2')
except FileNotFoundError as e:
    print(f"Error: Could not load resource file. {e}")
    print("Please make sure you have run the 'create_kb.py' script first.")
    sys.exit(1)

# --- CORE FUNCTIONS ---

def find_similar_examples(text):
    query_embedding = embedding_model.encode([text]).astype('float32')
    distances, indices = index.search(query_embedding, TOP_K_EXAMPLES)
    retrieved_examples = [rephrased_texts[i] for i in indices[0]]
    return retrieved_examples

def build_chat_messages(original_text, examples):
    """
    Builds the message history for the Ollama chat API.
    """
    system_prompt = """You are an expert writing assistant for a customer support team. Your task is to rephrase a given message to be more professional, empathetic, and clear. Use the style and tone from the examples I provide."""
    
    # Ensure we have enough examples to avoid index out of bounds
    safe_examples = examples + [""] * (TOP_K_EXAMPLES - len(examples))

    user_prompt = f"""Here are some examples of successfully rephrased messages:

Example 1: "{safe_examples[0]}"
Example 2: "{safe_examples[1]}"
Example 3: "{safe_examples[2]}"

---

Now, please rephrase the following message using the same style.
Do not explain your work or add any extra text, just provide the rephrased message itself.

Original Message: "{original_text}"
"""

    return [
        {"role": "system", "content": system_prompt},
        {"role": "user", "content": user_prompt}
    ]

def rephrase_text(messages):
    """
    Sends the chat messages to the Ollama API and returns the rephrased text.
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

# --- MAIN EXECUTION ---

if __name__ == "__main__":
    if len(sys.argv) > 1:
        input_text = " ".join(sys.argv[1:])
    else:
        print("Please enter the text you want to rephrase (press Ctrl+D when done):")
        input_text = sys.stdin.read().strip()

    if not input_text:
        print("No input text provided. Exiting.")
        sys.exit(0)

    similar_examples = find_similar_examples(input_text)
    chat_messages = build_chat_messages(input_text, similar_examples)
    rephrased_output = rephrase_text(chat_messages)
    
    if rephrased_output:
        print("\n" + "="*20 + " REPHRASED TEXT " + "="*20)
        print(rephrased_output)
        print("="*58)