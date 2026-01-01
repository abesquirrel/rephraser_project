# Rephraser Application

A premium AI-powered support analyst rephrasing tool with a modern PHP/Alpine.js frontend and Flask backend.

## Quick Start

Run the entire application with a single command:

```bash
./start.sh
```

This will:

- Start the Flask backend on `http://localhost:5001`
- Start the PHP frontend on `http://localhost:8000`
- Display status messages and logs
- Stop both servers when you press `Ctrl+C`

## First-Time Setup

If you haven't set up the project yet:

```bash
# Create virtual environment
python3 -m venv venv

# Install dependencies
./venv/bin/pip install -r requirements.txt

# Run the application
./start.sh
```

## Manual Startup (Alternative)

If you prefer to run servers separately:

```bash
# Terminal 1 - Flask Backend
./venv/bin/python app.py

# Terminal 2 - PHP Frontend
php -S localhost:8000 index.php
```

## Features

- **AI-Powered Rephrasing**: Transform support notes into professional responses
- **Template Mode**: Direct template-based responses with guaranteed signature
- **Online Research**: Optional web search integration for context
- **Knowledge Base**: Learn from approved responses
- **Response History**: Latest response prominently displayed, archive for older items
- **Visual Feedback**: Clear confirmation when responses are saved

## Project Structure

- `app.py` - Flask backend with LLM integration
- `index.php` - Premium Alpine.js frontend
- `start.sh` - Unified startup script
- `requirements.txt` - Python dependencies
- `ui.py` - Legacy Streamlit UI (deprecated)

## Logs

When using `start.sh`, logs are saved to:

- `flask.log` - Backend logs
- `php.log` - Frontend logs

## Stopping the Application

Press `Ctrl+C` in the terminal where `start.sh` is running. Both servers will shut down gracefully.
