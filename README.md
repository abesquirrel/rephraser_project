# Rephraser AI Project

A professional, microservice-based application for knowledge-aware text rephrasing interactions. This system leverages local LLMs (via Ollama) and a RAG (Retrieval-Augmented Generation) pipeline to provide consistent, high-quality responses based on your own historical data.

---

## 🏗 Architecture

The project is built on a containerized microservice architecture:

```mermaid
graph TD
    User[Web Browser] -->|HTTP:8000| Nginx[Nginx Gateway]
    Nginx -->|Proxy| Laravel[Laravel App (Frontend/Backend)]
    Laravel -->|SQL| MariaDB[(MariaDB Database)]
    Laravel -->|Rephrase Requests| AI[Python AI Service :5001]
    AI -->|Embedding Search| FAISS[(FAISS Vector Store)]
    AI -->|Inference| Ollama[Ollama (Host Machine)]
    AI -->|SQL (Index Build)| MariaDB
```

- **Laravel (App)**: Handles the UI (Blade + Alpine.js), business logic, and database management.
- **Python AI Service**: specialized Flask API that handles RAG, Vector Search (FAISS), and LLM Prompt Engineering.
- **MariaDB**: The "Source of Truth" for all knowledge base entries and audit logs.
- **Redis**: Caching and queue management (optional, configured for future scaling).
- **Ollama**: External LLM provider running on the host machine.

---

## 🚀 Prerequisites

Before deploying, ensure you have the following installed on your host machine:

1.  **Docker Desktop** (or Docker Engine + Docker Compose).
2.  **Ollama**: This application relies on local LLMs.
    - [Download Ollama](https://ollama.com)
    - **Pull Required Models**:
      ```bash
      ollama pull llama3:8b-instruct-q3_K_M
      ollama pull mistral:latest
      ollama pull gemma2:9b
      ```
      _(Note: `llama3:8b-instruct-q3_K_M` is optimized for speed/memory balance on consumer hardware)_

---

## 🛠 Deployment From Scratch

Follow these steps to deploy the application on a fresh machine:

### 1. Clone the Repository

```bash
git clone <repository-url>
cd rephraser_project
```

### 2. Configure Environment

A helper script is provided, but manually ensuring the `.env` exists is good practice.

```bash
cp laravel/.env.example laravel/.env
```

_Note: The default credentials in `.env.example` pre-configured to work with the Docker stack._

### 3. Start the Application

We provide a startup script that handles permissions, builds images, and runs migrations.

```bash
chmod +x start_docker.sh
./start_docker.sh
```

**What this script does:**

1.  Sets file permissions for storage directories.
2.  Builds the Docker images (`app` and `ai`).
3.  Starts the containers in detached mode.
4.  Runs Laravel database migrations to set up the schema.
5.  Clears caches.

### 4. Access the Application

- **Main Dashboard**: [http://localhost:8000](http://localhost:8000)
- **AI Service Docs (Swagger)**: [http://localhost:5001/docs](http://localhost:5001/docs)

---

## ⚙️ Configuration

### Laravel (`laravel/.env`)

Key variables to verify:

- `DB_HOST=db`: Connects to the internal Docker service.
- `AI_SERVICE_URL=http://ai:5001`: Internal URL for the Python service.

### AI Service (`docker-compose.yml`)

The AI service has safe defaults for 16GB RAM machines:

- **Environment Variables**:
  - `DB_HOST`: Database container name.
  - `AI_SERVICE_KEY`: API Key for internal security (default: `default_secret_key`).

---

## 📚 Usage Guide

### 1. The Generator

- **Input**: Paste your rough notes or draft into the left panel.
- **Generate**: Click the button. The AI uses your Knowledge Base to find similar past examples and rephrase your text using the preferred tone.
- **Approve**: If the output is good, click **"Approve"**. This is critical—it saves the result to the Knowledge Base, making the AI smarter for next time.

### 2. A/B Testing (Dual Model Mode)

- Toggle **"Dual Model Mode"** in the sidebar.
- Select two different models (e.g., Llama 3 vs Mistral).
- Click Generate. You will see two columns.
- **Edit & Compare**: You can edit either response directly.
- **Approve**: Click "Approve" on the version you prefer. The system records which model provided the better answer.

### 3. Knowledge Base

- **Import**: Upload a CSV with `original_text` and `rephrased_text` columns to bulk-train the system.
- **Manual Entry**: Manually add specific templates or difficult edge cases.

---

## 🔧 Troubleshooting

### "Ollama Connection Failed"

- **Cause**: The container cannot see Ollama on the host.
- **Fix**: Ensure Ollama is running (`ollama serve`). The architecture assumes `host.docker.internal` access. If on Linux, you may need to add `--add-host=host.docker.internal:host-gateway` to `docker-compose.yml`.

### "DB Connection Error"

- **Cause**: The database container is initializing slower than the app.
- **Fix**: The `start_docker.sh` manages this, but you can retry running migrations manually:
  ```bash
  docker-compose exec app php artisan migrate
  ```

### "None found in Notes section" (AI Error)

- **Cause**: The AI Model is refusing the task or hallucinating that the input is empty.
- **Fix**: We have optimized the prompts for Llama 3. Ensure you have the latest code. If it persists, try increasing the "Temperature" slightly in the sidebar settings.

### Rebuilding After Changes

If you modify `app.py` or `requirements.txt`:

```bash
docker-compose up -d --build ai
```

If you modify Laravel PHP code:

```bash
# Usually instant (mapped volume), but for asset changes:
npm run build
# (Run this inside the laravel directory or container)
```

---

## 📦 Backup & Maintenance

- **Database**: The database volume is persistent `mariadb_data`.
- **Export**: You can export your learned Knowledge Base as a CSV from the UI created in the database.
