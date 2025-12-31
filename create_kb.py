import csv
import faiss
import numpy as np
import pickle
from sentence_transformers import SentenceTransformer
from config import KNOWLEDGE_BASE_CSV, FAISS_INDEX_PATH, REPHRASED_TEXTS_PATH

# Use the same lightweight, all-purpose model for creating the embeddings.
embedding_model = SentenceTransformer('all-MiniLM-L6-v2')

def create_and_store_faiss_index():
    """
    Reads the knowledge base CSV, generates embeddings for the 'original_text',
    and stores them in a FAISS index. The rephrased texts are saved separately.
    """
    original_texts = []
    rephrased_texts = []
    
    try:
        with open(KNOWLEDGE_BASE_CSV, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            for row in reader:
                original_texts.append(row['original_text'])
                rephrased_texts.append(row['rephrased_text'])

    except FileNotFoundError:
        print(f"Error: '{KNOWLEDGE_BASE_CSV}' not found. Please make sure the file exists.")
        return

    if not original_texts:
        print("No documents found in the CSV. The FAISS index will not be created.")
        return

    print(f"Generating embeddings for {len(original_texts)} documents...")
    
    # Generate embeddings for all documents.
    embeddings = embedding_model.encode(original_texts, convert_to_tensor=False)
    
    # FAISS requires a numpy array of float32.
    embeddings = np.array(embeddings).astype('float32')
    
    # Create a FAISS index. IndexFlatL2 is a simple and effective one for this use case.
    d = embeddings.shape[1]  # Dimensionality of the vectors
    index = faiss.IndexFlatL2(d)
    
    # Add the vectors to the index.
    index.add(embeddings)
    
    # Save the index to a file.
    faiss.write_index(index, FAISS_INDEX_PATH)
    
    # Save the rephrased texts list to a separate file.
    with open(REPHRASED_TEXTS_PATH, "wb") as f:
        pickle.dump(rephrased_texts, f)
        
    print(f"Successfully created and saved FAISS index with {index.ntotal} vectors.")
    print("FAISS index and rephrased texts are ready.")

if __name__ == "__main__":
    create_and_store_faiss_index()