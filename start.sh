#!/bin/bash

# Rephraser Application Startup Script
# This script starts both the Flask backend and PHP frontend servers

set -e

echo "🚀 Starting Rephraser Application..."

# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "❌ Virtual environment not found. Please run: python3 -m venv venv && ./venv/bin/pip install -r requirements.txt"
    exit 1
fi

# Function to cleanup background processes on exit
cleanup() {
    echo ""
    echo "🛑 Shutting down servers..."
    if [ ! -z "$FLASK_PID" ]; then
        kill $FLASK_PID 2>/dev/null || true
    fi
    if [ ! -z "$PHP_PID" ]; then
        kill $PHP_PID 2>/dev/null || true
    fi
    echo "✅ Servers stopped"
    exit 0
}

# Set trap to cleanup on script exit
trap cleanup SIGINT SIGTERM EXIT

# Kill any existing processes on ports 5001 and 8000
echo "🧹 Cleaning up existing processes..."
lsof -ti :5001 | xargs kill -9 2>/dev/null || true
lsof -ti :8000 | xargs kill -9 2>/dev/null || true
sleep 1

# Start Flask backend
echo "🐍 Starting Flask backend on http://localhost:5001..."
./venv/bin/python app.py > flask.log 2>&1 &
FLASK_PID=$!

# Wait for Flask to start (check log for success message)
echo "   Waiting for Flask to initialize..."
for i in {1..10}; do
    sleep 1
    if lsof -i :5001 > /dev/null 2>&1; then
        echo "✅ Flask backend running (PID: $FLASK_PID)"
        break
    fi
    if [ $i -eq 10 ]; then
        echo "❌ Flask backend failed to start. Check flask.log for details."
        cat flask.log
        exit 1
    fi
done

# Start PHP frontend
echo "🌐 Starting PHP frontend on http://localhost:8000..."
php -S localhost:8000 index.php > php.log 2>&1 &
PHP_PID=$!
sleep 1

# Check if PHP started successfully
if ! lsof -i :8000 > /dev/null 2>&1; then
    echo "❌ PHP frontend failed to start. Check php.log for details."
    exit 1
fi
echo "✅ PHP frontend running (PID: $PHP_PID)"

echo ""
echo "🎉 Rephraser Application is ready!"
echo "📱 Open http://localhost:8000 in your browser"
echo ""
echo "Press Ctrl+C to stop all servers..."
echo ""

# Keep script running and wait for user interrupt
wait
