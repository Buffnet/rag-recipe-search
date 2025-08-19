<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeChunk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RecipeSearchService
{
    public function __construct(
        private EmbeddingService $embeddingService
    ) {}
    
    private function getSqlSearchService(): SqlRecipeSearchService
    {
        return app(SqlRecipeSearchService::class);
    }

    public function searchByIngredients(string $ingredients, int $limit = 10): Collection
    {
        // Try vector search first, fallback to SQL search
        if ($this->hasEmbeddings()) {
            return $this->vectorSearch($ingredients, RecipeChunk::CHUNK_TYPE_INGREDIENTS, $limit);
        }
        
        return $this->getSqlSearchService()->searchByIngredients($ingredients, $limit);
    }

    public function searchByCuisine(string $query, int $limit = 10): Collection
    {
        if ($this->hasEmbeddings()) {
            return $this->vectorSearch($query, RecipeChunk::CHUNK_TYPE_TITLE_META, $limit);
        }
        
        return $this->getSqlSearchService()->searchByCuisine($query, $limit);
    }

    public function searchByDishName(string $dishName, int $limit = 10): Collection
    {
        if ($this->hasEmbeddings()) {
            return $this->vectorSearch($dishName, RecipeChunk::CHUNK_TYPE_TITLE_META, $limit);
        }
        
        return $this->getSqlSearchService()->searchByDishName($dishName, $limit);
    }

    public function searchByInstructions(string $query, int $limit = 10): Collection
    {
        if ($this->hasEmbeddings()) {
            return $this->vectorSearch($query, RecipeChunk::CHUNK_TYPE_INSTRUCTIONS, $limit);
        }
        
        // For SQL fallback, use general search for instructions
        return $this->getSqlSearchService()->generalSearch($query, $limit);
    }

    public function generalSearch(string $query, int $limit = 10): Collection
    {
        if ($this->hasEmbeddings()) {
            return $this->vectorSearch($query, null, $limit);
        }
        
        return $this->getSqlSearchService()->generalSearch($query, $limit);
    }

    private function vectorSearch(string $query, ?string $chunkType = null, int $limit = 10): Collection
    {
        // Generate embedding for the query
        $queryEmbedding = $this->embeddingService->generateEmbedding($query);
        
        if (!$queryEmbedding) {
            return collect();
        }

        // Convert embedding to PostgreSQL vector format
        $vectorString = $this->embeddingService->formatVectorForPostgres($queryEmbedding);

        // Build the SQL query
        $sql = "
            SELECT 
                rc.recipe_id,
                rc.chunk_type,
                rc.content,
                r.name,
                r.category,
                r.area,
                r.thumbnail_url,
                r.youtube_url,
                (1 - (rc.embedding <=> ?::vector)) as similarity_score
            FROM recipe_chunks rc
            JOIN recipes r ON rc.recipe_id = r.id
            WHERE rc.embedding IS NOT NULL
        ";
        
        $bindings = [$vectorString];

        // Add chunk type filter if specified
        if ($chunkType) {
            $sql .= " AND rc.chunk_type = ?";
            $bindings[] = $chunkType;
        }

        $sql .= "
            ORDER BY rc.embedding <=> ?::vector
            LIMIT ?
        ";
        
        $bindings[] = $vectorString;
        $bindings[] = $limit;

        $results = DB::select($sql, $bindings);

        return $this->formatSearchResults(collect($results));
    }

    public function hybridSearch(string $query, int $limit = 10): Collection
    {
        if ($this->hasEmbeddings()) {
            // Try different search approaches and combine results
            $ingredientResults = $this->searchByIngredients($query, $limit / 2);
            $titleResults = $this->searchByCuisine($query, $limit / 2);
            
            // Merge and deduplicate by recipe_id
            $combined = $ingredientResults->concat($titleResults)
                ->groupBy('recipe_id')
                ->map(function ($group) {
                    // Take the result with highest similarity score for each recipe
                    return $group->sortByDesc('similarity_score')->first();
                })
                ->values()
                ->sortByDesc('similarity_score')
                ->take($limit);

            return $combined;
        }
        
        return $this->getSqlSearchService()->hybridSearch($query, $limit);
    }

    private function formatSearchResults(Collection $results): Collection
    {
        return $results->map(function ($result) {
            return [
                'recipe_id' => $result->recipe_id,
                'name' => $result->name,
                'category' => $result->category,
                'area' => $result->area,
                'thumbnail_url' => $result->thumbnail_url,
                'youtube_url' => $result->youtube_url,
                'similarity_score' => round($result->similarity_score, 4),
                'matched_content' => $result->content,
                'matched_type' => $result->chunk_type,
            ];
        });
    }

    public function findSimilarRecipes(int $recipeId, int $limit = 5): Collection
    {
        if ($this->hasEmbeddings()) {
            // Get the recipe's ingredient chunk
            $ingredientChunk = RecipeChunk::where('recipe_id', $recipeId)
                ->where('chunk_type', RecipeChunk::CHUNK_TYPE_INGREDIENTS)
                ->whereNotNull('embedding')
                ->first();

            if (!$ingredientChunk) {
                return collect();
            }

            // Parse the embedding from PostgreSQL vector format
            $embedding = $this->parseEmbeddingFromDB($ingredientChunk->embedding);
            
            if (!$embedding) {
                return collect();
            }

            $vectorString = $this->embeddingService->formatVectorForPostgres($embedding);
            
            $results = DB::select("
                SELECT 
                    rc.recipe_id,
                    r.name,
                    r.category,
                    r.area,
                    r.thumbnail_url,
                    (1 - (rc.embedding <=> ?::vector)) as similarity_score
                FROM recipe_chunks rc
                JOIN recipes r ON rc.recipe_id = r.id
                WHERE rc.embedding IS NOT NULL
                    AND rc.chunk_type = ?
                    AND rc.recipe_id != ?
                ORDER BY rc.embedding <=> ?::vector
                LIMIT ?
            ", [
                $vectorString,
                RecipeChunk::CHUNK_TYPE_INGREDIENTS,
                $recipeId,
                $vectorString,
                $limit
            ]);

            return $this->formatSearchResults(collect($results));
        }
        
        return $this->getSqlSearchService()->findSimilarRecipes($recipeId, $limit);
    }

    private function parseEmbeddingFromDB($embedding): ?array
    {
        if (is_string($embedding)) {
            return $this->embeddingService->parseVectorFromPostgres($embedding);
        }
        
        // If it's already an array (from Eloquent casting)
        return is_array($embedding) ? $embedding : null;
    }

    public function getSearchStats(): array
    {
        return $this->getSqlSearchService()->getSearchStats();
    }
    
    private function hasEmbeddings(): bool
    {
        return RecipeChunk::whereNotNull('embedding')->exists();
    }
}