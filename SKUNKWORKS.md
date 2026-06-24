# Project Masha: Skunkworks Manifest 🐈‍⬛

**Classification: Level 4 Technical Overview**

This document details the internal mechanics, security posture, and RAG optimization strategies for Project Masha.

---

## 1. Cognitive Architecture

Masha is designed around a **Twin-Engine AI Pipeline**:

### A. The Semantic Retriever (`ai-embedding`)
- **Engine**: `all-MiniLM-L6-v2` (Sentence-Transformers)
- **Vector DB**: FAISS (FlatL2 Indexing)
- **Optimization**: Cold-boot caching. Embeddings are persisted in the SQL ledger as binary blobs to bypass re-computation on service restart.
- **Security**: Restricted internal REST API with header-based token verification.

### B. The Inference Reactor (`ai-inference`)
- **Engine**: Ollama-hosted LLMs (Optimized for `Llama-3` or `Gemma-2`)
- **Latency Reduction**:
  - **Technical Fast-Path**: Regex-based extraction of IMEIs/Serial numbers to bypass expensive LLM-keyword extraction.
  - **Asynchronous Context Gathering**: Parallel execution of KB retrieval and Web Research.
- **Streaming**: Native Server-Sent Events (SSE) emulation for real-time UI updates.

---

## 2. Security Protocols (Skunkworks Standard)

### A. Data Sovereignty
- **Zero-Cloud Leakage**: All inference is performed on the local substrate (Ollama). No prompt data is transmitted to 3rd-party providers (OpenAI/Anthropic).
- **Redaction**: System logs are automatically stripped of sensitive raw input to prevent accidental PII exposure in monitoring tools.

### B. Interface Security
- **Internal Hardening**: The AI services are isolated within the `rephraser-network`. Access is mediated via the `AI_SERVICE_KEY` protocol.
- **CSRF/XSS**: Laravel middleware handles standard web-tier protection, with strict Alpine.js sanitization on the frontend.

---

## 3. Operational Roadmap

### Phase 1: Foundation (Current)
- [x] Local RAG implementation.
- [x] Multi-role persona engine.
- [x] Technical data persistence.

### Phase 2: Edge Optimization (Upcoming)
- [ ] Multi-GPU inference load balancing.
- [ ] Dynamic LoRA adapter switching based on Role.
- [ ] Federated learning from user approvals.

### Phase 3: Autonomous Synthesis
- [ ] Multi-agent task decomposition.
- [ ] Real-time document ingestion (PDF/OCR).

---

## 4. Maintenance & Operations

- **Index Optimization**: `POST /trigger_rebuild` triggers a fresh sweep of the SQL ledger to rebuild the FAISS index.
- **Knowledge Pruning**: Entries with low retrieval utility (hits < threshold) can be autonomously flagged for decommission via the `CleanupController`.

---

> *"The best way to predict the future is to build it in a basement."*
