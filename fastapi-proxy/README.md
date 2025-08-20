# FastAPI Proxy for Laravel Recipe Search

High-performance async proxy layer for the Laravel Recipe Search API with caching, parallel processing, and enhanced features.

## Features

🚀 **Async Performance**
- Async HTTP client for Laravel API calls
- Parallel search execution
- Non-blocking Redis caching

⚡ **Caching Layer**
- Redis-based response caching (1 hour TTL)
- Automatic cache key generation
- Cache statistics and management endpoints

📚 **Auto-Generated Documentation**
- Interactive API docs at `/docs`
- ReDoc documentation at `/redoc`
- Complete Pydantic type validation

🔍 **Enhanced Search**
- All Laravel endpoints proxied
- Parallel multi-strategy search
- Response time tracking
- Cache hit/miss indicators

## Quick Start

### 1. Install Dependencies
```bash
cd fastapi-proxy
pip install -r requirements.txt
```

### 2. Start Redis (Optional - for caching)
```bash
# Using Docker
docker run -d -p 6379:6379 redis:alpine

# Or using brew on macOS
brew install redis
brew services start redis
```

### 3. Start FastAPI Server
```bash
# Development mode with hot reload
uvicorn main:app --host 0.0.0.0 --port 8001 --reload

# Production mode
python main.py
```

### 4. Access the API
- **FastAPI Proxy**: http://localhost:8001
- **API Documentation**: http://localhost:8001/docs
- **Health Check**: http://localhost:8001/

## API Endpoints

### Search Endpoints
- `POST /search/ingredients` - Search by ingredients
- `POST /search/cuisine` - Search by cuisine type
- `POST /search/dish` - Search by dish name
- `POST /search/general` - General text search
- `POST /search/hybrid` - Hybrid search strategy
- `POST /search/similar/{recipe_id}` - Find similar recipes
- `POST /search/enhanced` - **NEW** Parallel multi-strategy search

### Statistics & Management
- `GET /` - Health check and service info
- `GET /stats` - Search statistics from Laravel + proxy info
- `GET /cache/stats` - Redis cache statistics
- `DELETE /cache/clear` - Clear all cached data

## Enhanced Features

### 1. Response Caching
All search responses are cached in Redis for 1 hour:
```json
{
  "query": "chicken rice",
  "results": [...],
  "cached": true,
  "response_time_ms": 15
}
```

### 2. Parallel Enhanced Search
The `/search/enhanced` endpoint runs multiple search strategies in parallel:
- General search
- Ingredient search  
- Dish name search

Results are combined, deduplicated, and ranked by similarity score.

### 3. Type Validation
Full Pydantic validation for all requests:
```python
class IngredientSearchRequest(BaseModel):
    ingredients: str = Field(..., min_length=1, max_length=500)
    limit: int = Field(10, ge=1, le=50)
```

### 4. Comprehensive Logging
All requests, cache hits/misses, and errors are logged for monitoring.

## Performance Comparison

| Metric | Laravel Direct | FastAPI Proxy (uncached) | FastAPI Proxy (cached) |
|--------|----------------|--------------------------|------------------------|
| Response Time | ~200ms | ~220ms | ~15ms |
| Concurrent Requests | Limited | High (async) | Very High |
| Enhanced Search | N/A | N/A | Multiple parallel calls |

## Development

### Running Tests
```bash
# Install test dependencies
pip install pytest pytest-asyncio httpx

# Run tests (when implemented)
pytest tests/
```

### Environment Configuration
Modify these variables in `main.py`:
```python
LARAVEL_BASE_URL = "http://localhost:8080/api"
REDIS_URL = "redis://localhost:6379"
CACHE_TTL = 3600  # seconds
```

## Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Client App    │───▶│  FastAPI Proxy  │───▶│   Laravel API   │
│                 │    │                 │    │                 │
│ - Web Browser   │    │ - Async Client  │    │ - Recipe Search │
│ - Mobile App    │    │ - Redis Cache   │    │ - SQL Database  │
│ - External API  │    │ - Parallel Ops  │    │ - TheMealDB     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌─────────────────┐
                       │   Redis Cache   │
                       │                 │
                       │ - 1hr TTL       │
                       │ - JSON Storage  │
                       │ - Statistics    │
                       └─────────────────┘
```

## Use Cases

1. **Performance Layer**: Add caching without modifying Laravel
2. **API Gateway**: Single entry point for multiple services
3. **Modern Features**: Async, parallel processing, auto-docs
4. **Monitoring**: Response times, cache statistics, health checks
5. **Development**: Fast iteration with hot reload and validation