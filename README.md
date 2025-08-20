# RAG Recipe Search System - Laravel 12 + FastAPI

**AI-Powered Recipe Discovery Platform** built with Laravel 12, PostgreSQL, and FastAPI proxy layer.

This system uses **Retrieval-Augmented Generation (RAG)** patterns to provide intelligent recipe search based on ingredients, cuisine types, and cooking preferences. Currently uses SQL-based search with upgrade path to vector embeddings.

## 🎯 Project Overview

- **Backend**: Laravel 12 + PHP 8.3 with PostgreSQL 16 + pgvector
- **Search Engine**: SQL-based text search (cost-effective MVP)
- **Frontend**: Responsive Blade templates + AJAX
- **API Layer**: Laravel JSON API + FastAPI async proxy
- **Data Source**: 110+ recipes from TheMealDB API
- **Container**: Docker Compose with all services

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose v2
- Python 3.11+ (for FastAPI proxy)

### 1. Start the Laravel Application
```bash
make up
make init   # Creates Laravel 12 project in ./src and configures environment
```

### 2. Import Recipe Data
```bash
make artisan ARGS="migrate"
make artisan ARGS="recipes:import --limit=100"
```

### 3. Access the Applications
- **Recipe Search Web UI**: http://localhost:8080
- **Laravel API**: http://localhost:8080/api
- **FastAPI Proxy**: http://localhost:8001 (see fastapi-proxy/README.md)
- **API Documentation**: http://localhost:8001/docs

## 🔍 Search Capabilities

### Web Interface Features:
- 🥕 **Ingredient Search**: "chicken, rice, tomatoes"
- 🌍 **Cuisine Search**: "Italian", "Japanese", "Mexican"
- 🍽️ **Dish Name Search**: "pasta", "curry", "salad"
- 🔍 **General Search**: "spicy chicken with vegetables"

### API Endpoints:
```bash
# Search by ingredients
curl -X POST http://localhost:8080/api/search/ingredients \
  -H "Content-Type: application/json" \
  -d '{"ingredients": "chicken, rice"}'

# Search by cuisine
curl -X POST http://localhost:8080/api/search/cuisine \
  -H "Content-Type: application/json" \
  -d '{"cuisine": "Italian"}'

# Get search statistics
curl http://localhost:8080/api/search/stats
```

## 📊 Services

- **nginx**: `:8080` - Web server and Laravel app
- **php-fpm (app)**: PHP 8.3 with Composer and Laravel
- **postgres**: `:54322` (internal `postgres:5432`) - Database with pgvector
- **redis**: `:63790` - Caching and sessions
- **mailpit**: `:8025` - Email testing sandbox
- **node**: Node 20 for frontend builds
- **FastAPI Proxy**: `:8001` - High-performance async API layer

## 🛠 Makefile Commands

### Laravel Development:
- `make up` - Start all containers in background
- `make down` - Stop and remove containers
- `make logs` - View container logs
- `make init` - Create Laravel 12 project and configure environment
- `make composer ARGS="..."` - Run Composer in app container
- `make artisan ARGS="..."` - Execute Artisan commands
- `make tinker` - Laravel Tinker REPL
- `make test` - Run PHPUnit tests

### Recipe Data Management:
- `make artisan ARGS="migrate"` - Run database migrations
- `make artisan ARGS="recipes:import --limit=100"` - Import recipes from TheMealDB
- `make artisan ARGS="recipes:import --limit=500"` - Import more recipes

### Frontend Development:
- `make npm-install` - Install Node.js dependencies
- `make npm-dev` - Start development build with watch
- `make npm-build` - Production build

## 🎨 Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Web Browser   │───▶│   Laravel App   │───▶│  PostgreSQL DB  │
│                 │    │                 │    │                 │
│ - Recipe Search │    │ - Blade Views   │    │ - 110+ Recipes  │
│ - AJAX Calls    │    │ - API Routes    │    │ - Ingredients   │
│ - Responsive    │    │ - SQL Search    │    │ - Text Chunks   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌─────────────────┐
                       │  FastAPI Proxy  │
                       │                 │
                       │ - Async Client  │
                       │ - Redis Cache   │
                       │ - Parallel Ops  │
                       └─────────────────┘
```

## 📁 Project Structure

```
├── src/                          # Laravel 12 application
│   ├── app/
│   │   ├── Models/              # Recipe, RecipeChunk, RecipeIngredient
│   │   ├── Services/            # TheMealDB, RecipeSearch, SqlRecipeSearch
│   │   ├── Http/Controllers/    # Web and API controllers
│   │   └── Console/Commands/    # Recipe import command
│   ├── resources/views/         # Blade templates
│   ├── routes/                  # Web and API routes
│   └── database/migrations/     # Database schema
├── fastapi-proxy/               # FastAPI async proxy layer
├── docker/                      # Docker configuration
└── claude.md                    # Detailed project documentation
```

## 🔧 Environment Configuration

### Database Settings (`.env.docker`):
```env
POSTGRES_DB=app
POSTGRES_USER=app
POSTGRES_PASSWORD=app
POSTGRES_PORT=54322
```

### Laravel Environment (auto-generated in `src/.env`):
```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=app
```

## 📈 Performance Features

| Feature | Description | Status |
|---------|-------------|--------|
| SQL Search | Fast text-based search with relevance scoring | ✅ Active |
| Recipe Caching | Database-level query optimization | ✅ Active |
| AJAX Interface | No page reloads for search | ✅ Active |
| FastAPI Proxy | Async request handling with Redis cache | ✅ Optional |
| Vector Embeddings | Semantic search with OpenAI | ⏳ Future upgrade |

## 🧪 Development Workflow

### Adding New Recipes:
```bash
# Import specific number of recipes
make artisan ARGS="recipes:import --limit=50"

# Check import status
make artisan ARGS="recipes:import --status"
```

### API Development:
```bash
# Test search endpoints
curl -X POST http://localhost:8080/api/search/ingredients \
  -H "Content-Type: application/json" \
  -d '{"ingredients": "beef, potatoes"}'
```

### Frontend Development:
1. Edit Blade templates in `src/resources/views/`
2. Modify CSS/JS in template files
3. Test responsive design on http://localhost:8080

## 🚀 Deployment Notes

- **Production**: Use environment-specific `.env` files
- **Scaling**: FastAPI proxy supports high concurrent loads
- **Monitoring**: Built-in health checks and statistics endpoints
- **Upgrades**: Architecture supports vector embeddings when budget allows

## 📚 Additional Resources

- **Detailed Documentation**: See `claude.md` for complete technical details
- **API Documentation**: http://localhost:8001/docs (when FastAPI is running)
- **Database Schema**: Check `src/database/migrations/` for table structures
- **TheMealDB API**: External recipe data source documentation

---

**Happy cooking and coding!** 🍳👨‍💻
