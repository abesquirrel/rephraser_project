# Rephraser AI Project

A professional microservice-based application for knowledge-aware text rephrasing, featuring a Laravel frontend, a Python AI microservice, and a MariaDB database.

## Architecture

- **Web UI (Laravel)**: Modern, responsive interface for rephrasing and KB management.
- **AI Service (Python/Flask)**: Handles vector search (FAISS), LLM synthesis, and web search integration.
- **Database (MariaDB 10.11)**: Persistent storage for 3,000+ knowledge base entries.
- **Redis**: Queue and caching layer.
- **Nginx**: High-performance reverse proxy.

## Key Features

- **Knowledge-Aware AI**: prioritizes "Template" entries for structured responses.
- **Hybrid Search**: Combines FAISS vector retrieval with DuckDuckGo web search.
- **Observability**: Built-in latency tracking and token estimation for all generations.
- **Health Monitoring**: Automated Docker health checks for all core infrastructure services.
- **Interactive API Docs**: Swagger/OpenAPI documentation available at `/docs`.

## Quick Start

### 1. Requirements

- Docker and Docker Compose
- Ollama (running on the host) with `llama3:8b-instruct-q3_K_M`

### 2. Startup

```bash
./start_docker.sh
```

### 3. Access

- **Web Interface**: `http://localhost:8000`
- **Streamlit UI**: `streamlit run ui.py --server.address 0.0.0.0`
- **API Documentation**: `http://localhost:5001/docs`

## Management

### LAN Access

The application is accessible from other devices on your network at `http://<YOUR_IP>:8000`.

### Database

- **Host Port**: `3310`
- **User**: `rephraser`
- **Pass**: `secret`
