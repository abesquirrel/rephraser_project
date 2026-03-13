# Masha AI: Development Guide

This guide covers the technical setup and maintenance procedures for the Masha AI ecosystem.

## 🛠️ Local Development Setup

### 1. Environment Configuration
The project uses a shared `.env` file in the `laravel/` directory and environment variables in `docker-compose.yml`.

**Critical Variables:**
- `GEMINI_API_KEY`: Required for Google GenAI models.
- `AI_SERVICE_KEY`: Shared secret between Laravel and Python microservices (default: `default_secret_key`).
- `OLLAMA_MODEL`: Default local model (e.g., `llama3:8b-instruct-q3_K_M`).

### 2. Service Ports
| Service | External Port | Internal Port |
| :--- | :--- | :--- |
| Laravel Gateway | 8000 | 80 |
| AI Inference | 5001 | 5001 |
| AI Embedding | 5002 | 5002 |
| MariaDB | 3310 | 3306 |

---

## 🎭 Prompt Role Management

Prompt Roles are stored in the `prompt_roles` table and define the "personality" of the AI.

### Adding a New Role
New roles can be added via the UI or directly in the database.
- **Identity**: The high-level system instruction (e.g., "You are a Technical Writer").
- **Protocol**: The behavioral rules (e.g., "Always use active voice").
- **Format**: The expected output structure (must include `{signature}`).

Example SQL:
```sql
INSERT INTO prompt_roles (name, identity, protocol_override, format_override) 
VALUES ('legal_counsel', 'You are a Legal Assistant.', 'Use formal language. Avoid liability.', 'Regards,\n{signature}');
```

---

## 🧠 Knowledge Base (KB) Maintenance

### Manual Rebuild
The FAISS index is loaded into memory on startup. If you modify the `knowledge_bases` table directly, you must trigger a rebuild:
```bash
curl -X POST http://localhost:5002/trigger_rebuild -H "X-AI-KEY: your_secret_key"
```

### KB Optimization & Pruning
The system tracks `hits` for every entry. You can prune underperforming entries:
```bash
curl -X POST http://localhost:5002/cleanup -H "X-AI-KEY: your_secret_key" -d '{"threshold_hits": 2}'
```
*Note: This only deletes entries older than 7 days that have fewer than the threshold hits.*

---

## 🔍 Troubleshooting

- **Check Inference Logs**: `docker-compose logs -f ai-inference`
- **Verify Vector Status**: `curl http://localhost:5002/health`
- **Reset Database**: `php artisan migrate:fresh --seed` (Warning: Data Loss)
