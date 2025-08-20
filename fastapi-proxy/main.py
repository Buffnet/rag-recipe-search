"""
FastAPI Proxy for Laravel Recipe Search API
Provides async performance layer with caching and enhanced features
"""

import json
import hashlib
from typing import List, Dict, Any, Optional
from datetime import datetime, timedelta
import httpx
import redis
from fastapi import FastAPI, HTTPException, BackgroundTasks
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
import asyncio
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Pydantic models for request/response validation
class SearchRequest(BaseModel):
    query: str = Field(..., min_length=1, max_length=500, description="Search query")
    limit: int = Field(10, ge=1, le=50, description="Number of results to return")

class IngredientSearchRequest(BaseModel):
    ingredients: str = Field(..., min_length=1, max_length=500, description="Comma-separated ingredients")
    limit: int = Field(10, ge=1, le=50)

class CuisineSearchRequest(BaseModel):
    cuisine: str = Field(..., min_length=1, max_length=200, description="Cuisine type")
    limit: int = Field(10, ge=1, le=50)

class DishSearchRequest(BaseModel):
    dish: str = Field(..., min_length=1, max_length=200, description="Dish name")
    limit: int = Field(10, ge=1, le=50)

class RecipeResult(BaseModel):
    recipe_id: int
    name: str
    category: str
    area: str
    thumbnail_url: Optional[str]
    youtube_url: Optional[str]
    similarity_score: float
    matched_content: str
    matched_type: str

class SearchResponse(BaseModel):
    query: str
    search_type: str
    results: List[RecipeResult]
    count: int
    cached: bool = False
    response_time_ms: int

# FastAPI app configuration
app = FastAPI(
    title="Recipe Search Proxy API",
    description="High-performance proxy for Laravel Recipe Search with caching and async features",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Configure appropriately for production
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Configuration
LARAVEL_BASE_URL = "http://localhost:8080/api"
REDIS_URL = "redis://localhost:6379"  # Update based on your Redis setup
CACHE_TTL = 3600  # 1 hour cache

# Global clients
http_client: Optional[httpx.AsyncClient] = None
redis_client: Optional[redis.Redis] = None

@app.on_event("startup")
async def startup_event():
    """Initialize HTTP and Redis clients"""
    global http_client, redis_client
    
    http_client = httpx.AsyncClient(
        base_url=LARAVEL_BASE_URL,
        timeout=30.0,
        headers={"Accept": "application/json", "Content-Type": "application/json"}
    )
    
    try:
        redis_client = redis.from_url(REDIS_URL, decode_responses=True)
        redis_client.ping()  # Test connection
        logger.info("Redis connected successfully")
    except Exception as e:
        logger.warning(f"Redis connection failed: {e}. Caching disabled.")
        redis_client = None

@app.on_event("shutdown")
async def shutdown_event():
    """Cleanup clients"""
    global http_client
    if http_client:
        await http_client.aclose()

def generate_cache_key(endpoint: str, data: Dict[str, Any]) -> str:
    """Generate cache key from endpoint and request data"""
    cache_string = f"{endpoint}:{json.dumps(data, sort_keys=True)}"
    return hashlib.md5(cache_string.encode()).hexdigest()

async def get_cached_result(cache_key: str) -> Optional[Dict[str, Any]]:
    """Get cached result if available"""
    if not redis_client:
        return None
    
    try:
        cached_data = redis_client.get(cache_key)
        if cached_data:
            return json.loads(cached_data)
    except Exception as e:
        logger.warning(f"Cache read error: {e}")
    return None

async def set_cached_result(cache_key: str, data: Dict[str, Any]) -> None:
    """Cache the result"""
    if not redis_client:
        return
    
    try:
        redis_client.setex(cache_key, CACHE_TTL, json.dumps(data))
    except Exception as e:
        logger.warning(f"Cache write error: {e}")

async def proxy_laravel_request(endpoint: str, data: Dict[str, Any]) -> Dict[str, Any]:
    """Make async request to Laravel API"""
    if not http_client:
        raise HTTPException(status_code=500, detail="HTTP client not initialized")
    
    try:
        response = await http_client.post(endpoint, json=data)
        response.raise_for_status()
        return response.json()
    except httpx.HTTPStatusError as e:
        raise HTTPException(status_code=e.response.status_code, detail=f"Laravel API error: {e}")
    except httpx.RequestError as e:
        raise HTTPException(status_code=500, detail=f"Request failed: {e}")

@app.get("/", tags=["Health"])
async def root():
    """Health check endpoint"""
    return {
        "service": "Recipe Search Proxy API",
        "status": "healthy",
        "laravel_api": LARAVEL_BASE_URL,
        "cache_enabled": redis_client is not None,
        "timestamp": datetime.utcnow().isoformat()
    }

@app.get("/stats", tags=["Statistics"])
async def get_search_stats():
    """Get search statistics from Laravel API"""
    start_time = datetime.utcnow()
    
    try:
        if not http_client:
            raise HTTPException(status_code=500, detail="HTTP client not initialized")
            
        response = await http_client.get("/search/stats")
        response.raise_for_status()
        result = response.json()
        
        # Add proxy metadata
        result["proxy_info"] = {
            "cache_enabled": redis_client is not None,
            "response_time_ms": int((datetime.utcnow() - start_time).total_seconds() * 1000)
        }
        
        return result
    except httpx.HTTPStatusError as e:
        raise HTTPException(status_code=e.response.status_code, detail=f"Laravel API error: {e}")
    except httpx.RequestError as e:
        raise HTTPException(status_code=500, detail=f"Request failed: {e}")

@app.post("/search/ingredients", response_model=SearchResponse, tags=["Search"])
async def search_by_ingredients(request: IngredientSearchRequest):
    """Search recipes by ingredients with caching"""
    start_time = datetime.utcnow()
    
    # Generate cache key
    cache_key = generate_cache_key("ingredients", request.dict())
    
    # Try cache first
    cached_result = await get_cached_result(cache_key)
    if cached_result:
        cached_result["cached"] = True
        cached_result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
        return cached_result
    
    # Make Laravel API request
    laravel_data = {"ingredients": request.ingredients, "limit": request.limit}
    result = await proxy_laravel_request("/search/ingredients", laravel_data)
    
    # Add metadata
    result["cached"] = False
    result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
    
    # Cache the result
    await set_cached_result(cache_key, result)
    
    return result

@app.post("/search/cuisine", response_model=SearchResponse, tags=["Search"])
async def search_by_cuisine(request: CuisineSearchRequest):
    """Search recipes by cuisine with caching"""
    start_time = datetime.utcnow()
    
    cache_key = generate_cache_key("cuisine", request.dict())
    cached_result = await get_cached_result(cache_key)
    
    if cached_result:
        cached_result["cached"] = True
        cached_result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
        return cached_result
    
    laravel_data = {"cuisine": request.cuisine, "limit": request.limit}
    result = await proxy_laravel_request("/search/cuisine", laravel_data)
    
    result["cached"] = False
    result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
    
    await set_cached_result(cache_key, result)
    return result

@app.post("/search/dish", response_model=SearchResponse, tags=["Search"])
async def search_by_dish(request: DishSearchRequest):
    """Search recipes by dish name with caching"""
    start_time = datetime.utcnow()
    
    cache_key = generate_cache_key("dish", request.dict())
    cached_result = await get_cached_result(cache_key)
    
    if cached_result:
        cached_result["cached"] = True
        cached_result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
        return cached_result
    
    laravel_data = {"dish": request.dish, "limit": request.limit}
    result = await proxy_laravel_request("/search/dish", laravel_data)
    
    result["cached"] = False
    result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
    
    await set_cached_result(cache_key, result)
    return result

@app.post("/search/general", response_model=SearchResponse, tags=["Search"])
async def general_search(request: SearchRequest):
    """General recipe search with caching"""
    start_time = datetime.utcnow()
    
    cache_key = generate_cache_key("general", request.dict())
    cached_result = await get_cached_result(cache_key)
    
    if cached_result:
        cached_result["cached"] = True
        cached_result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
        return cached_result
    
    laravel_data = {"q": request.query, "limit": request.limit}
    result = await proxy_laravel_request("/search/general", laravel_data)
    
    result["cached"] = False
    result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
    
    await set_cached_result(cache_key, result)
    return result

@app.post("/search/hybrid", response_model=SearchResponse, tags=["Search"])
async def hybrid_search(request: SearchRequest):
    """Hybrid search combining multiple strategies"""
    start_time = datetime.utcnow()
    
    cache_key = generate_cache_key("hybrid", request.dict())
    cached_result = await get_cached_result(cache_key)
    
    if cached_result:
        cached_result["cached"] = True
        cached_result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
        return cached_result
    
    laravel_data = {"q": request.query, "limit": request.limit}
    result = await proxy_laravel_request("/search/hybrid", laravel_data)
    
    result["cached"] = False
    result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
    
    await set_cached_result(cache_key, result)
    return result

@app.post("/search/similar/{recipe_id}", tags=["Search"])
async def find_similar_recipes(recipe_id: int, limit: int = 5):
    """Find similar recipes"""
    start_time = datetime.utcnow()
    
    cache_key = generate_cache_key("similar", {"recipe_id": recipe_id, "limit": limit})
    cached_result = await get_cached_result(cache_key)
    
    if cached_result:
        cached_result["cached"] = True
        cached_result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
        return cached_result
    
    laravel_data = {"limit": limit}
    result = await proxy_laravel_request(f"/search/similar/{recipe_id}", laravel_data)
    
    result["cached"] = False
    result["response_time_ms"] = int((datetime.utcnow() - start_time).total_seconds() * 1000)
    
    await set_cached_result(cache_key, result)
    return result

@app.post("/search/enhanced", tags=["Enhanced Search"])
async def enhanced_search(request: SearchRequest):
    """
    Enhanced search that combines multiple search strategies in parallel
    This demonstrates the power of async FastAPI
    """
    start_time = datetime.utcnow()
    
    try:
        # Run multiple searches in parallel
        tasks = [
            proxy_laravel_request("/search/general", {"q": request.query, "limit": request.limit // 2}),
            proxy_laravel_request("/search/ingredients", {"ingredients": request.query, "limit": request.limit // 2}),
            proxy_laravel_request("/search/dish", {"dish": request.query, "limit": request.limit // 2}),
        ]
        
        results = await asyncio.gather(*tasks, return_exceptions=True)
        
        # Process results
        combined_results = []
        successful_searches = 0
        
        search_types = ["general", "ingredients", "dish"]
        for i, result in enumerate(results):
            if isinstance(result, dict) and "results" in result:
                combined_results.extend(result["results"])
                successful_searches += 1
            else:
                logger.warning(f"{search_types[i]} search failed: {result}")
        
        # Remove duplicates and sort by similarity score
        seen_ids = set()
        unique_results = []
        for recipe in combined_results:
            if recipe["recipe_id"] not in seen_ids:
                seen_ids.add(recipe["recipe_id"])
                unique_results.append(recipe)
        
        # Sort by similarity score and limit results
        unique_results.sort(key=lambda x: x["similarity_score"], reverse=True)
        final_results = unique_results[:request.limit]
        
        response_time = int((datetime.utcnow() - start_time).total_seconds() * 1000)
        
        return {
            "query": request.query,
            "search_type": "enhanced_parallel",
            "results": final_results,
            "count": len(final_results),
            "successful_searches": successful_searches,
            "total_searches": len(tasks),
            "cached": False,
            "response_time_ms": response_time
        }
        
    except Exception as e:
        logger.error(f"Enhanced search error: {e}")
        raise HTTPException(status_code=500, detail=f"Enhanced search failed: {e}")

@app.get("/cache/stats", tags=["Cache Management"])
async def cache_stats():
    """Get cache statistics"""
    if not redis_client:
        return {"cache_enabled": False, "message": "Redis not available"}
    
    try:
        info = redis_client.info()
        return {
            "cache_enabled": True,
            "connected_clients": info.get("connected_clients", 0),
            "used_memory_human": info.get("used_memory_human", "unknown"),
            "keyspace_hits": info.get("keyspace_hits", 0),
            "keyspace_misses": info.get("keyspace_misses", 0),
            "cache_ttl_seconds": CACHE_TTL
        }
    except Exception as e:
        return {"cache_enabled": False, "error": str(e)}

@app.delete("/cache/clear", tags=["Cache Management"])
async def clear_cache():
    """Clear all cached data"""
    if not redis_client:
        return {"cache_enabled": False, "message": "Redis not available"}
    
    try:
        redis_client.flushdb()
        return {"success": True, "message": "Cache cleared successfully"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Failed to clear cache: {e}")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)