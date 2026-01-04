# Rephraser AI Project

A professional microservice-based application for knowledge-aware text rephrasing. It uses a Laravel frontend, a Python AI microservice (FAISS + LLM), and MariaDB.

## 🚀 Quick Start (Deployment from Scratch)

### 1. Prerequisites

- **Docker & Docker Compose** installed.
- **Ollama** installed on the host machine.
- Download required models:
  ```bash
  ollama pull gemma2:9b
  ollama pull mistral
  ollama pull llama3:8b-instruct-q3_K_M
  ```

### 2. Startup

1. Clone the repository and enter the directory.
2. Initialize environment (optional, script handles most):
   ```bash
   cp laravel/.env.example laravel/.env
   ```
3. Boot the stack:
   ```bash
   chmod +x start_docker.sh
   ./start_docker.sh
   ```

### 3. Access

- **Web UI**: [http://localhost:8000](http://localhost:8000)
- **API Docs**: [http://localhost:5001/docs](http://localhost:5001/docs)

---

## 🧠 Knowledge Base Training

The AI is "context-aware" and relies on its Knowledge Base (KB) to provide structured, accurate rephrasing. Following deployment, you **must train its memory**:

### Option A: Bulk Import (Recommended)

1. Go to the **"Learned Corpus"** tab in the UI.
2. Upload a CSV file containing your past rephrasing examples.
3. The system will vectorize and index these entries automatically.

### Option B: Interactive Learning

1. Use the **"Generator"** tab to draft responses.
2. Click **"✨ Generate Response"**.
3. If the result is good, click **"✅ Approve & Learn"**.
4. This adds the pair to the KB, making future generations more accurate.

---

## 🛠 Management & Architecture

- **Architecture**:
  - `laravel/`: PHP/Alpine.js frontend.
  - `ai-service/`: Python/Flask AI logic (FAISS vector store).
  - `mariadb`: Persistent storage for audit logs and KB.
- **Manual KB Entry**: Use the "Learned Corpus" tab to manually add `Original -> Rephrased` pairs with specific categories and keywords.
- **LAN Access**: Accessible via `http://<YOUR_IP>:8000`.

---

## ⚖️ A/B Comparison

Enable **"A/B Comparison"** to test two models side-by-side (e.g., Gemma2 vs Mistral). The system tracks which model was used for every approved response in the **"Audit Trail"**.
