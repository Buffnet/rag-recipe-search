# RAG Recipe Search Project - Context for Claude Code

## 🎯 Project Overview
Building a RAG (Retrieval-Augmented Generation) pipeline for recipe recommendations using Laravel. Users input available ingredients or cuisine preferences, and the system returns relevant recipes using vector similarity search.

## 🛠 Tech Stack
- **Backend**: Laravel 12 + PHP 8.3
- **Database**: PostgreSQL 16 + pgvector extension
- **Vector Search**: OpenAI embeddings (text-embedding-3-small)
- **Data Source**: TheMealDB API (free)
- **Container**: Docker Compose setup
- **Frontend**: Simple Blade templates (for MVP)

## 📊 Database Schema

### Core Tables
```sql
-- Main recipes table
recipes (
    id SERIAL PRIMARY KEY,
    meal_id VARCHAR UNIQUE,
    name VARCHAR NOT NULL,
    category VARCHAR,
    area VARCHAR,
    thumbnail_url TEXT,
    youtube_url TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Vector chunks for RAG search
recipe_chunks (
    id SERIAL PRIMARY KEY,
    recipe_id INTEGER REFERENCES recipes(id),
    chunk_type VARCHAR NOT NULL, -- 'title_meta', 'ingredients', 'instructions'
    content TEXT NOT NULL,
    embedding vector(1536), -- OpenAI embedding dimension
    created_at TIMESTAMP
);

-- Ingredients storage
recipe_ingredients (
    id SERIAL PRIMARY KEY,
    recipe_id INTEGER REFERENCES recipes(id),
    ingredient VARCHAR NOT NULL,
    measure VARCHAR
);
```

## 🔍 RAG Strategy

### Chunking Approach (Simplified)
1. **Chunk Type: 'title_meta'**
    - Content: "Recipe Name. Category: X. Cuisine: Y"
    - Use case: Search by dish name or cuisine

2. **Chunk Type: 'ingredients'**
    - Content: "ingredient1, ingredient2, ingredient3, ..."
    - Use case: Search by available ingredients

3. **Chunk Type: 'instructions'**
    - Content: Full cooking instructions
    - Use case: Search by cooking method or technique

### Search Types
- **Ingredient-based**: "I have chicken, rice, soy sauce"
- **Cuisine-based**: "Italian recipes", "Japanese cuisine"
- **Dish name**: "pasta carbonara", "teriyaki"

## 🏗 Project Structure

```
app/
├── Console/Commands/
│   └── ImportRecipesCommand.php      # TheMealDB data import
├── Http/Controllers/
│   ├── RecipeController.php          # Recipe CRUD
│   └── SearchController.php          # RAG search endpoints
├── Services/
│   ├── TheMealDBService.php          # API integration
│   ├── EmbeddingService.php          # OpenAI embeddings
│   ├── RecipeSearchService.php       # Vector similarity search
│   └── ChunkingService.php           # Text chunking logic
└── Models/
    ├── Recipe.php
    ├── RecipeChunk.php
    └── RecipeIngredient.php

database/migrations/
├── create_recipes_table.php
├── create_recipe_chunks_table.php
├── create_recipe_ingredients_table.php
└── add_vector_extension.php

routes/
├── web.php                           # Web interface
└── api.php                           # API endpoints
```

## 🌐 TheMealDB API Integration

### Key Endpoints
- `GET /search.php?s={meal_name}` - Search by name
- `GET /filter.php?i={ingredient}` - Filter by ingredient
- `GET /categories.php` - Get all categories
- `GET /list.php?a=list` - Get all areas (cuisines)
- `GET /random.php` - Random recipe

### Sample Response Structure
```json
{
  "idMeal": "52772",
  "strMeal": "Teriyaki Chicken Casserole", 
  "strCategory": "Chicken",
  "strArea": "Japanese",
  "strInstructions": "Preheat oven to 180C...",
  "strMealThumb": "https://www.themealdb.com/images/...",
  "strIngredient1": "soy sauce",
  "strMeasure1": "3/4 cup",
  // ... up to 20 ingredients
}
```

## 🎯 MVP Goals

### Phase 1: Data Foundation
- [ ] Setup pgvector extension
- [ ] Create database migrations
- [ ] Import 500+ recipes from TheMealDB
- [ ] Generate embeddings for all chunks

### Phase 2: RAG Implementation
- [ ] Vector similarity search
- [ ] Three search types (ingredients, cuisine, dish name)
- [ ] API endpoints for search
- [ ] Response ranking and filtering

### Phase 3: User Interface
- [ ] Simple chat interface
- [ ] Recipe display with images
- [ ] Search history and favorites
- [ ] Mobile-responsive design

## 🔧 Development Commands

### Docker Environment
```bash
# Start containers
docker compose --env-file .env.docker up -d

# Run Laravel commands
docker compose --env-file .env.docker exec app php artisan make:command ImportRecipes
docker compose --env-file .env.docker exec app php artisan migrate
docker compose --env-file .env.docker exec app php artisan recipes:import
```

### Database Setup
```bash
# Add pgvector extension
docker compose --env-file .env.docker exec postgres psql -U app -d app -c "CREATE EXTENSION IF NOT EXISTS vector;"

# Run migrations
docker compose --env-file .env.docker exec app php artisan migrate
```

## 🌟 Feature Ideas (Future)
- Recipe difficulty scoring
- Nutritional information integration
- Shopping list generation
- Recipe modification suggestions
- Voice input for hands-free cooking
- Meal planning calendar
- User rating and reviews

## 🔑 Environment Variables Needed
```env
# OpenAI for embeddings
OPENAI_API_KEY=your_key_here

# Database (already configured)
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=app
DB_USERNAME=app
DB_PASSWORD=app
```

## 📝 Current Status
- ✅ Laravel 12 setup with Docker
- ✅ PostgreSQL configured
- ⏳ Next: Setup pgvector and create migrations
- ⏳ Then: Import recipes from TheMealDB API

## 💡 Tips for Development
1. Start with small dataset (50-100 recipes) for testing
2. Use Laravel factories for test data
3. Cache embeddings to avoid regenerating
4. Log search queries for optimization
5. Consider rate limiting for OpenAI API calls

## 🚀 Quick Start
```bash
# Clone and setup
git clone [repository]
cd laravel-docker-starter
make init

# Install dependencies  
docker compose --env-file .env.docker exec app composer install

# Setup database
docker compose --env-file .env.docker exec app php artisan migrate

# Import recipes
docker compose --env-file .env.docker exec app php artisan recipes:import --limit=100
```

---
*This file serves as context for Claude Code to understand the project structure, goals, and current progress.*
