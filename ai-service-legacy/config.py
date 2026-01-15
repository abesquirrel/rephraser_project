# --- CONFIGURATION ---
OLLAMA_API_URL = "http://localhost:11434/api/chat"
OLLAMA_MODEL = "llama3:8b-instruct-q3_K_M"
FAISS_INDEX_PATH = "faiss_index.bin"
REPHRASED_TEXTS_PATH = "rephrased_texts.pkl"
KNOWLEDGE_BASE_CSV = "knowledge_base.csv"
LOG_FILE = "rephraser.log"
TOP_K_EXAMPLES = 2
WEB_SEARCH_RESULT_COUNT = 3
MAX_GENERATION_TOKENS = 750
