# RAG Recipe Search Project - Context for Claude Code

## 🎯 Project Overview
Building a RAG (Retrieval-Augmented Generation) pipeline for recipe recommendations using Laravel. Users input available ingredients or cuisine preferences, and the system returns relevant recipes using vector similarity search.

## 🛠 Tech Stack
- **Backend**: Laravel 12 + PHP 8.3
- **Database**: PostgreSQL 16 + pgvector extension
- **Vector Search**: OpenAI embeddings (text-embedding-3-small)
- **Data Source**: TheMealDB API (free)
- **Container**: Docker Compose setup
- **Frontend**: Blade templates + AJAX (hybrid approach for MVP)

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

## 🔍 Current Search Implementation (SQL-based)

**Using PostgreSQL full-text search instead of vector embeddings**

### SQL Search Features:
1. **Ingredient Search**: Match by ingredient names using ILIKE
2. **Recipe Name Search**: Fuzzy matching on recipe titles
3. **Cuisine/Category Search**: Filter by area and category
4. **Combined Search**: Multi-criteria search with ranking
5. **Similarity Scoring**: Custom relevance scoring based on matches

### Search Query Examples:
```sql
-- Ingredient-based search
SELECT r.*, COUNT(ri.id) as ingredient_matches 
FROM recipes r 
JOIN recipe_ingredients ri ON r.id = ri.recipe_id 
WHERE ri.ingredient ILIKE '%chicken%' OR ri.ingredient ILIKE '%rice%'
GROUP BY r.id 
ORDER BY ingredient_matches DESC;

-- Name and cuisine search  
SELECT * FROM recipes 
WHERE name ILIKE '%pasta%' 
   OR area ILIKE '%italian%' 
   OR category ILIKE '%pasta%'
ORDER BY 
  CASE WHEN name ILIKE '%pasta%' THEN 3
       WHEN area ILIKE '%italian%' THEN 2  
       WHEN category ILIKE '%pasta%' THEN 1
       ELSE 0 END DESC;
```

### Migration Path:
- ✅ **Phase 1**: SQL text search (current)
- ⏳ **Phase 2**: Elasticsearch for advanced text search
- ⏳ **Phase 3**: Vector embeddings for semantic search

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
├── web.php                           # Blade templates + AJAX
└── api.php                           # JSON API endpoints

resources/views/
├── layouts/app.blade.php             # Main layout with CSS/JS
├── search/index.blade.php            # Search interface
├── recipes/show.blade.php            # Recipe details page
└── partials/recipe-card.blade.php    # Recipe card component
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

### Phase 1: Data Foundation ✅ COMPLETED
- ✅ Setup pgvector extension
- ✅ Create database migrations
- ✅ Import 110+ recipes from TheMealDB
- ✅ Recipe chunks and ingredients stored (SQL search ready)

### Phase 2: RAG Implementation ✅ COMPLETED
- ✅ SQL-based similarity search (cost-effective alternative)
- ✅ Three search types (ingredients, cuisine, dish name)
- ✅ API endpoints for search
- ✅ Response ranking and filtering

### Phase 3: User Interface 🔄 IN PROGRESS
- 🔄 Hybrid Blade + AJAX search interface
- 🔄 Recipe cards with images and details
- ⏳ Search history and favorites
- ⏳ Mobile-responsive design

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
- ✅ **Phase 1 & 2 Complete**: Laravel 12 + PostgreSQL + SQL search
- ✅ **110+ recipes** imported from TheMealDB API
- ✅ **API endpoints** working: ingredients, cuisine, dish name, general search
- ✅ **SQL-based search** with relevance scoring (cost-effective MVP)
- 🔄 **Phase 3 In Progress**: Blade + AJAX user interface
- ⏳ **Future upgrades**: Vector embeddings when budget allows

## 🎨 Frontend Architecture (Phase 3)

### Hybrid Approach: Blade + AJAX
- **Blade templates**: Server-side rendering for SEO and initial load
- **AJAX calls**: Dynamic search without page reloads
- **Progressive enhancement**: Works without JavaScript
- **Mobile-first**: Responsive design with CSS Grid/Flexbox

### User Interface Features:
1. **Search Interface**: Multi-tab search (ingredients, cuisine, dish name)
2. **Real-time Results**: AJAX calls to existing API endpoints
3. **Recipe Cards**: Thumbnail images from TheMealDB CDN
4. **Recipe Details**: Full recipe view with ingredients and instructions
5. **Responsive Design**: Works on mobile and desktop

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
