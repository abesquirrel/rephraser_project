#!/bin/bash
echo "🚀 Starting Docker Environment..."
docker-compose up -d --build

echo "⏳ Waiting for Database to Initialize..."
sleep 15

echo "📦 Running Database Migrations..."
docker-compose exec -T app php artisan migrate

echo "✅ Environment Ready!"
echo "📱 Access App at: http://localhost:8123"
