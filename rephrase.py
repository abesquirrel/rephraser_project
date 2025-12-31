import requests
import json
import sys
from core import find_similar_examples, rephrase_text_cli
from config import TOP_K_EXAMPLES

from sentence_transformers import SentenceTransformer

embedding_model = SentenceTransformer('all-MiniLM-L6-v2')

# --- CORE FUNCTIONS ---

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

    similar_examples = find_similar_examples(input_text, embedding_model)
    chat_messages = build_chat_messages(input_text, similar_examples)
    rephrased_output = rephrase_text_cli(chat_messages)
    
    if rephrased_output:
        print("\n" + "="*20 + " REPHRASED TEXT " + "="*20)
        print(rephrased_output)
        print("="*58)