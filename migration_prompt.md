# App Migration Prompt

**Goal**: Migrate the "Masha: The Cat" Rephraser application from a heavy Dockerized Laravel + dual-Python microservice architecture to a significantly lighter, more cost-efficient, and performant stack, while strictly utilizing the Google Cloud Vertex AI free tier (Gemini 2.5 Flash).

**Current Architecture Context**:
- Frontend: Blade + Alpine.js + TailwindCSS.
- Backend Core: Laravel 11 (PHP), MariaDB, Redis.
- AI Services: Two heavy Python Flask Docker containers (one for Inference/PII redaction, one for FAISS vector embeddings).
- Features: PII redaction (IMEIs, MSISDNs), FAISS-based Knowledge Base retrieval, DuckDuckGo web research context, AI streaming, session tracking, and audit logging.

## Core Directives for the AI Agent

### 1. Framework & Infrastructure Target
- **Primary Framework**: Migrate the frontend and primary API routing to **Astro (SSR mode)**. Astro will significantly reduce the payload, serve HTML rapidly, and allows us to reuse the existing TailwindCSS and Alpine.js logic almost 1:1, dropping Laravel Blade.
- **Database**: Replace MariaDB with a lighter SQLite-based approach (e.g., Cloudflare D1 or LibSQL/Turso) to handle sessions, audit logs, and KB entries without the memory overhead of a traditional SQL server.
- **Docker Isolation for Sensitive Data**: We MUST retain a lightweight Docker container exclusively for PII data processing (Sanitizing IMEIs, phone numbers, and sensitive carrier data). Create a fast, single-purpose microservice (e.g., using Python FastAPI or Go) that accepts text, redacts it, and returns the redaction map.

### 2. Vertex AI & Gemini 2.5 Flash Integration
- Replace the direct Google GenAI API and Ollama calls with the **Vertex AI SDK**.
- Hardcode the model to `gemini-2.5-flash`.
- Embeddings: Use Vertex AI's `text-embedding-004` (or latest free-tier equivalent) to replace the heavy Gemini local embedding script.

### 3. Strict Cost & Limit Controls (CRITICAL)
Implement robust guardrails to ensure we stay within the Vertex AI free tier:
- **Token Tracking**: Implement a token counter interceptor. Before sending a request to Vertex, calculate the approximate prompt tokens. If a daily threshold is nearing, reject or queue the request.
- **Rate Limiting**: Implement strict IP/Session-based rate limiting (e.g., max 15 requests per minute per user) to prevent burst usage from exhausting the quota.
- **Max Tokens Clamp**: Force a hard limit of `max_output_tokens` (e.g., 800) on every single API call, overriding any user preset if it exceeds the safe limit.
- **Caching**: Cache identical web searches and frequent KB retrievals to prevent redundant LLM or embedding API calls.

### 4. Feature Parity Requirements
Ensure the new Astro + Microservice architecture maintains:
1. **Real-time Streaming**: The UI must still receive streaming token chunks using Server-Sent Events (SSE) or readable streams via Astro API endpoints.
2. **PII Redaction Flow**: The text must hit the Dockerized PII sanitizer -> Vertex AI -> PII Restorer -> User.
3. **Knowledge Base (RAG)**: Migrate the FAISS index to a lightweight vector solution compatible with serverless/Astro (e.g., Cloudflare Vectorize, or a local SQLite vector extension like `sqlite-vss`).
4. **Web Research**: Retain the DuckDuckGo search integration, moving the logic to the Astro backend.

### 5. Execution Steps Required from You (The AI)
1. **Scaffold Astro**: Initialize an Astro SSR project. Port `welcome.blade.php` to `src/pages/index.astro`. Port the Alpine.js state to client-side scripts.
2. **Build the PII Docker Container**: Write a `Dockerfile` and a minimal API (Python/Go) focused purely on Regex PII extraction and restoration mapping.
3. **Database & Vector Migration**: Provide the schema for the new lightweight DB and the vector integration.
4. **Vertex AI Service**: Write the backend API route in Astro (`src/pages/api/rephrase.ts`) that orchestrates the PII call, Vector search, Web search, and Vertex AI streaming.
5. **Guardrails**: Write the middleware/logic for tracking Vertex usage and enforcing rate limits.

---
**Instructions for the AI**: Please acknowledge these requirements and output a step-by-step implementation plan. Do not remove any existing UX features. Prioritize security, Vertex AI limit prevention, and execution speed.
