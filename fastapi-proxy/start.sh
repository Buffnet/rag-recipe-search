#!/bin/bash
set -e

echo "🚀 Starting FastAPI Proxy for Laravel Recipe Search..."

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "📦 Creating Python virtual environment..."
    python3 -m venv venv
fi

# Activate virtual environment
echo "🔧 Activating virtual environment..."
source venv/bin/activate

# Install dependencies
echo "📚 Installing dependencies..."
pip install -r requirements.txt

# Start Redis if not running (optional)
if ! redis-cli ping > /dev/null 2>&1; then
    echo "⚠️  Redis not running - caching will be disabled"
    echo "   Start Redis with: docker run -d -p 6379:6379 redis:alpine"
fi

# Start FastAPI server
echo "🎯 Starting FastAPI server on http://localhost:8001"
echo "📖 API Documentation: http://localhost:8001/docs"
echo ""

uvicorn main:app --host 0.0.0.0 --port 8001 --reload