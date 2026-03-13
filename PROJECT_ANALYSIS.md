# Rephraser Project: Technical Deep Dive

## 1. Core Workflow: The RAG Pipeline

The system employs a sophisticated Retrieval-Augmented Generation (RAG) workflow designed for high-accuracy technical rephrasing while maintaining strict PII protection.

### Step 1: Input Redaction (PII Protection)
Before any text reaches an external LLM or is stored in logs, it passes through the `PIIManager` in the `ai-inference` service.
- **Regex-based Detection**:
  - `IMEI`: `\b\d{14,16}\b`
  - `PHONE`: `\b\d{10,12}\b`
  - `EMAIL`: `\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b`
- **Mapping Strategy**: Each detected PII is replaced with a unique placeholder (e.g., `[[IMEI_1]]`). The mapping is stored in memory during the request lifecycle and restored in the final token stream.

### Step 2: Context Gathering (Parallel Processing)
The `ai-inference` service triggers two parallel threads to maximize efficiency:
1. **Vector Retrieval**: Queries the `ai-embedding` service for the top-N most similar entries from the `knowledge_bases` table using FAISS (L2 Distance).
2. **Web Search**: Utilizes `duckduckgo_search` to gather real-time technical documentation from priority domains: `apple.com`, `samsung.com`, `t-mobile.com`, `tello.com`.

### Step 3: Prompt Synthesis & Generation
The `build_structured_prompt` function compiles a system prompt that includes:
- **Role Identity**: Dynamic system instructions based on the selected persona (Tech Support, Customer Service, etc.).
- **Formatting Constraints**: Strict rules (e.g., "Start exactly with 'Hello,'", "No Markdown").
- **Context Injection**: Redacted KB examples and Web search results are injected into the user message.

---

## 2. API Schema Specification

### Gateway (Laravel) -> Interface
- `POST /api/rephrase`: Orchestrates the entire generation flow. Returns a streamed JSON response.
- `POST /api/upload_kb`: Ingests CSV/JSON data into the MariaDB `knowledge_bases` table and triggers a background FAISS rebuild.

### AI Inference (Flask)
- `POST /rephrase`: Accepts redacted text, configuration (temperature, max_tokens), and role settings. Proxies to Ollama or Gemini.
- `POST /suggest_keywords`: Analyzes input text to generate optimized search/indexing tags.

### AI Embedding (Flask)
- `POST /retrieve`: Performs vector search. 
  - **Ranking Logic**: Results are boosted if they are marked as `is_template` (+10) or match the requested `category` (+20).
- `POST /trigger_rebuild`: Invalidates the in-memory FAISS index and rebuilds it from MariaDB blobs.

---

## 3. Data Persistence Layer

| Table | Description | Critical Fields |
| :--- | :--- | :--- |
| `knowledge_bases` | Vector-indexed support entries | `embedding` (BLOB), `hits`, `is_template`, `category` |
| `ai_response_logs` | Audit trail of all AI generations | `input_text`, `output_text`, `kb_ids`, `latency` |
| `prompt_roles` | Dynamic system instructions | `identity`, `protocol_override`, `format_override` |
| `api_calls` | Internal service telemetry | `endpoint`, `status`, `response_time` |

---

## 4. Search & Retrieval Strategy

The system prioritizes structural consistency over raw keyword matching:
1. **Template Preference**: When `template_mode` is enabled, the RAG engine strictly prioritizes entries marked as `is_template` to ensure the output follows established professional formats.
2. **Domain Scoping**: Web searches are restricted to known authoritative sources to avoid "hallucinating" facts from unreliable forums.
3. **Keyword Fallback**: If specific identifiers (IMEIs, MSISDNs) are missing, the system uses an LLM-driven keyword extractor to identify technical markers before searching.
`
