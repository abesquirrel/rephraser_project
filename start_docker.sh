#!/bin/bash

# Ensure AI_SERVICE_KEY is set
if [ -z "$AI_SERVICE_KEY" ]; then
    echo "⚠️  AI_SERVICE_KEY is not set. Generating a temporary key..."
    export AI_SERVICE_KEY=$(openssl rand -hex 16)
    echo "🔑 AI_SERVICE_KEY=$AI_SERVICE_KEY (Export this to your shell for persistent use)"
fi

echo "🚀 Starting Docker Environment..."
docker-compose up -d --build

echo "⏳ Waiting for Database to Initialize..."
sleep 15

echo "📦 Running Database Migrations..."
docker-compose exec -T app php artisan migrate

echo "✅ Environment Ready!"
echo "📱 Access App at: http://localhost:8000"
