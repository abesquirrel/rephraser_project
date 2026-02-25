# Rephraser Project: Comprehensive Analysis

## 1. System Overview

The **Rephraser Project** is an AI-powered technical support knowledge management and text-rephrasing platform. It transforms rough support notes, technical observations, and shorthand into polished, professional responses using Large Language Models (LLMs).

The system relies on a **microservice architecture** deployed via Docker Compose, combining a monolithic frontend/backend framework (Laravel/PHP) with specialized AI services (Python/Flask) for Retrieval-Augmented Generation (RAG) and intelligent orchestration.

### Key Capabilities:

- **Context-Aware Rephrasing:** Uses LLMs (via Ollama or Gemini API) to restructure text.
- **Dynamic Prompt Roles:** Adopts dynamic system prompts based on configurable roles (e.g., Tech Support, Customer Service).
- **Knowledge Base (RAG):** Uses a Vector Database (FAISS) to retrieve past successful rephrases and structural templates to augment generation context.
- **Web Search Integration:** Automatically researches technical terms via DuckDuckGo to fact-check outputs.
- **Analytics & Auditing:** Comprehensive tracking of token usage, prompt configurations, response timing, and knowledge base hits via MariaDB.

---

## 2. Infrastructure & Orchestration (`docker-compose.yml`)

The ecosystem runs within a Docker Bridge Network (`rephraser-network`) ensuring secure, isolated communication across services.

- **`app` & `web`**: The Laravel application container and its NGINX proxy (port 8000). Handles all client-facing requests, databases writing, and UI rendering.
- **`ai-inference`**: Python Flask microservice (port 5001) handling actual LLM context synthesis, web search, and model proxying for local Ollama instances (`host.docker.internal:11434`).
- **`ai-embedding`**: Python Flask microservice (port 5002) utilizing `sqlite/mariadb` blob storage and a local FAISS index for vector similarity search.
- **`db`**: MariaDB (10.11) container (port 3310 externally) storing structured data including users, audit logs, caching metrics, and vector blobs.
- **`redis`**: Manages Laravel queues, session data, and transient application caching.

---

## 3. Frontend Architecture (Laravel Blade + Alpine.js)

**Path:** `laravel/resources/views/welcome.blade.php` | `laravel/resources/js/app.js`

The User Interface is built as a highly reactive, single-page-like experience, using Laravel Blade for structural delivery, Tailwind CSS for styling, and Alpine.js for complex DOM manipulation and state management.

### Key Alpine.js Components (`rephraserApp` context):

- **Core State:** Manages `inputText`, `rephrasedContent`, the selected model (`modelA`), generation parameters (`temperature`, `maxTokens`), and modes (`templateMode`, `enableWebSearch`).
- **Generation Flow (`generateRephrase`):**
  - Gathers the user’s selected configurations, dynamic roles, and raw input text.
  - Opens an HTTP stream to the Laravel backend (`/api/rephrase`).
  - Progressively reads the stream utilizing `TextDecoder`. It handles JSON-encoded stream chunks to iteratively build out intermediate "Thinking" logs (`parsed.status`) and output text (`parsed.token`).
- **Response History & Archive:** Persistently caches responses (`history`), allowing users to approve (`approveHistoryEntry`), edit, or copy previous LLM generations.
- **Config & Tuning UI:** Exposes granular settings (Top-P, penalties) and adjusts safe maximums depending on the chosen model tier (e.g., restricting smaller models from excessive context limits).

---

## 4. Backend Gateway (Laravel)

**Path:** `laravel/app/Http/Controllers/RephraseController.php`

The Laravel application acts as the traffic controller, persistence layer, and security boundary for the AI features.

### Primary Endpoint: `/api/rephrase`

Instead of performing the complex logic directly, the Controller handles the lifecycle and telemetry of an AI request:

1. **Metadata & Security:** Sanitizes input strings and attaches standard metadata (Session IDs, user roles).
2. **Metric Initialization:** Creates a `ModelGeneration` log and an `ApiCall` record before processing, determining initial token approximations.
3. **Distribution:**
   - **Google Gemini:** If a Gemini model is chosen, it directly invokes the `Gemini::generativeModel` facade natively within PHP utilizing the retrieved context from `ai-embedding`.
   - **Local/Ollama:** If a local model is chosen, it proxies the request to the Python `ai-inference` service via HTTP streaming.
4. **Stream Traversal & Auditing:** As the data chunks return, it captures usage metadata injected natively into the end of the stream payload (latency, token usage, knowledge base hit IDs). This is sequentially saved to `AuditLog` and `KbUsage` tables.

### Other Responsibilities:

- **Role Management (`PromptRole`):** Provides CRUD operations for the dynamic system prompts selectable in the UI.
- **Knowledge Base (KB) Syncing:** Accepts manual additions and CSV uploads to the KB platform (`/api/upload_kb`). Once a change occurs, it calls `triggerRebuild()` on the `ai-embedding` microservice to update the vector space.
- **Optimization:** Processes cleanup requests and candidate pruning for underutilized knowledge base entries based on application hits and temporal checks.

---

## 5. Python Microservices: The AI Engines

### A. Inference Microservice (`ai-service-inference`)

**Path:** `ai-service-inference/app.py`
The orchestration layer for LLM prompt compilation and localized action planning.

- **Component: Information Retrieval:** Initiates internal parallel threads (`run_kb_search` and `run_web_search`). Uses the `DDGS` library (DuckDuckGo Search) to find facts referencing predefined domains (e.g., apple.com, t-mobile.com), and queries `ai-embedding` for prior context templates concurrently.
- **Component: Prompt Synthesis (`build_structured_prompt`):** Constructs a deterministic formatting sequence. It injects Negative Prompts, specific User Directives, the Web constraints, and sets strict constraints (`Formatting Constraints: NO MARKDOWN`, exactly start with `Hello,`).
- **Component: Streaming Execution (`call_llm_stream`):** Interfaces with Ollama's HTTP `/api/chat` API, emitting single tokens and `done_meta` statistics back up to Laravel.

### B. Embedding / RAG Microservice (`ai-service-embedding`)

**Path:** `ai-service-embedding/app.py`
The vector search engine powering the RAG implementation.

- **Engine Load (`load_knowledge_base`):** Runs a background thread bridging MySQL with Python. It queries the `knowledge_bases` table for texts.
- **Encoding Workflow:** Checks if an embedding blob exists. If it causes a cache miss, it generates a new vector using SentenceTransformers (`all-MiniLM-L6-v2`) and synchronizes it back to MariaDB.
- **Vector Space (FAISS):** Maintains an in-memory `IndexFlatL2` matrix representation.
- **Retrieval Workflow (`/retrieve`):** During a query, it encodes the incoming text and performs an L2 distance nearest-neighbor search (`faiss_index.search`). Results are artificially boosted in ranking logic if they are marked as templates (`prefer_templates`) or match the specified system domain categories.
- **Usage Telemetry:** Fires asynchronous background workers to increment the `hits` value on the source MariaDB items whenever they are successfully retrieved, tracking utility for pruning logic later on.

---

## 6. Structural Interaction Flow (LLM Readability Diagram)

```mermaid
sequenceDiagram
    participant User
    participant Frontend (Alpine.js)
    participant Backend (Laravel)
    participant DB (MariaDB)
    participant inference (AI Inference)
    participant embedding (AI Embedding + FAISS)
    participant Ollama/Gemini

    User->>Frontend (Alpine.js): Clicks 'Generate'
    Frontend (Alpine.js)->>Backend (Laravel): POST /api/rephrase
    Backend (Laravel)->>DB (MariaDB): Log `ModelGeneration` & `ApiCall` Start

    alt Is Gemini Model?
        Backend (Laravel)->>embedding (AI Embedding + FAISS): POST /retrieve
        embedding (AI Embedding + FAISS)-->>Backend (Laravel): Nearest Neighbors
        Backend (Laravel)->>Ollama/Gemini: Stream Generation with Context
    else Is Local Model?
        Backend (Laravel)->>inference (AI Inference): POST /rephrase
        par
            inference (AI Inference)->>DuckDuckGo: Web Search (Optional)
            inference (AI Inference)->>embedding (AI Embedding + FAISS): POST /retrieve
        end
        embedding (AI Embedding + FAISS)-->>inference (AI Inference): Nearest Vectors
        embedding (AI Embedding + FAISS)->>DB (MariaDB): Async update KB Hits
        inference (AI Inference)->>Ollama/Gemini: Stream Generation via `host.docker.internal`
        Ollama/Gemini-->>inference (AI Inference): Token Stream
        inference (AI Inference)-->>Backend (Laravel): Proxy Token Stream
    end

    Backend (Laravel)-->>Frontend (Alpine.js): Stream JSON Payloads
    Frontend (Alpine.js)->>User: Update UI Token-by-Token
    Backend (Laravel)->>DB (MariaDB): Flush exact Token usage & Metadata to Audit tables
```
