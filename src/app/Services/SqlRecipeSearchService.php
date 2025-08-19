<?php

namespace App\Services;

use App\Models\Recipe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class SqlRecipeSearchService
{
    public function searchByIngredients(string $ingredients, int $limit = 10): Collection
    {
        $ingredientTerms = $this->parseSearchTerms($ingredients);
        
        if (empty($ingredientTerms)) {
            return collect();
        }

        $query = Recipe::select('recipes.*')
            ->selectRaw('COUNT(DISTINCT ri.id) as ingredient_matches')
            ->join('recipe_ingredients as ri', 'recipes.id', '=', 'ri.recipe_id')
            ->where(function ($q) use ($ingredientTerms) {
                foreach ($ingredientTerms as $term) {
                    $q->orWhere('ri.ingredient', 'ILIKE', "%{$term}%");
                }
            })
            ->groupBy('recipes.id', 'recipes.meal_id', 'recipes.name', 'recipes.category', 
                     'recipes.area', 'recipes.thumbnail_url', 'recipes.youtube_url', 
                     'recipes.created_at', 'recipes.updated_at')
            ->orderByDesc('ingredient_matches')
            ->orderBy('recipes.name')
            ->limit($limit);

        $results = $query->get();
        
        return $this->formatResults($results, 'ingredients', $ingredients);
    }

    public function searchByCuisine(string $cuisine, int $limit = 10): Collection
    {
        $searchTerms = $this->parseSearchTerms($cuisine);
        
        $query = Recipe::select('recipes.*')
            ->selectRaw('
                CASE 
                    WHEN area ILIKE ? THEN 3
                    WHEN category ILIKE ? THEN 2
                    WHEN name ILIKE ? THEN 1
                    ELSE 0
                END as relevance_score
            ', ["%{$cuisine}%", "%{$cuisine}%", "%{$cuisine}%"])
            ->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('area', 'ILIKE', "%{$term}%")
                      ->orWhere('category', 'ILIKE', "%{$term}%")
                      ->orWhere('name', 'ILIKE', "%{$term}%");
                }
            })
            ->orderByDesc('relevance_score')
            ->orderBy('name')
            ->limit($limit);

        $results = $query->get();
        
        return $this->formatResults($results, 'cuisine', $cuisine);
    }

    public function searchByDishName(string $dishName, int $limit = 10): Collection
    {
        $searchTerms = $this->parseSearchTerms($dishName);
        
        $query = Recipe::select('recipes.*')
            ->selectRaw('
                CASE 
                    WHEN name ILIKE ? THEN 4
                    WHEN name ILIKE ? THEN 3
                    WHEN category ILIKE ? THEN 2
                    WHEN area ILIKE ? THEN 1
                    ELSE 0
                END as relevance_score
            ', ["%{$dishName}%", $this->buildFuzzyPattern($dishName), "%{$dishName}%", "%{$dishName}%"])
            ->where(function ($q) use ($searchTerms, $dishName) {
                $q->where('name', 'ILIKE', "%{$dishName}%");
                foreach ($searchTerms as $term) {
                    $q->orWhere('name', 'ILIKE', "%{$term}%")
                      ->orWhere('category', 'ILIKE', "%{$term}%");
                }
            })
            ->orderByDesc('relevance_score')
            ->orderBy('name')
            ->limit($limit);

        $results = $query->get();
        
        return $this->formatResults($results, 'dish_name', $dishName);
    }

    public function generalSearch(string $query, int $limit = 10): Collection
    {
        $searchTerms = $this->parseSearchTerms($query);
        
        if (empty($searchTerms)) {
            return collect();
        }

        // Multi-table search with different weights
        $results = collect();
        
        // Search in recipe names and metadata (highest priority)
        $recipeResults = Recipe::select('recipes.*')
            ->selectRaw('
                CASE 
                    WHEN name ILIKE ? THEN 5
                    WHEN area ILIKE ? THEN 4  
                    WHEN category ILIKE ? THEN 3
                    ELSE 2
                END as relevance_score
            ', ["%{$query}%", "%{$query}%", "%{$query}%"])
            ->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('name', 'ILIKE', "%{$term}%")
                      ->orWhere('area', 'ILIKE', "%{$term}%")
                      ->orWhere('category', 'ILIKE', "%{$term}%");
                }
            })
            ->get();

        // Search in ingredients (medium priority)
        $ingredientResults = Recipe::select('recipes.*')
            ->selectRaw('COUNT(DISTINCT ri.id) + 1 as relevance_score')
            ->join('recipe_ingredients as ri', 'recipes.id', '=', 'ri.recipe_id')
            ->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->orWhere('ri.ingredient', 'ILIKE', "%{$term}%");
                }
            })
            ->groupBy('recipes.id', 'recipes.meal_id', 'recipes.name', 'recipes.category', 
                     'recipes.area', 'recipes.thumbnail_url', 'recipes.youtube_url', 
                     'recipes.created_at', 'recipes.updated_at')
            ->get();

        // Merge and deduplicate results
        $combined = $recipeResults->concat($ingredientResults)
            ->keyBy('id')
            ->sortByDesc('relevance_score')
            ->take($limit)
            ->values();

        return $this->formatResults($combined, 'general', $query);
    }

    public function hybridSearch(string $query, int $limit = 10): Collection
    {
        // Combine different search strategies
        $ingredientResults = $this->searchByIngredients($query, $limit / 2);
        $nameResults = $this->searchByDishName($query, $limit / 2);
        $cuisineResults = $this->searchByCuisine($query, $limit / 2);
        
        // Merge and deduplicate by recipe_id
        $combined = $ingredientResults->concat($nameResults)
            ->concat($cuisineResults)
            ->groupBy('recipe_id')
            ->map(function ($group) {
                // Take the result with highest similarity score
                return $group->sortByDesc('similarity_score')->first();
            })
            ->values()
            ->sortByDesc('similarity_score')
            ->take($limit);

        return $combined;
    }

    public function findSimilarRecipes(int $recipeId, int $limit = 5): Collection
    {
        $recipe = Recipe::with('ingredients')->find($recipeId);
        
        if (!$recipe) {
            return collect();
        }

        $ingredients = $recipe->ingredients->pluck('ingredient')->implode(' ');
        
        // Find recipes with similar ingredients, excluding the current recipe
        $similarRecipes = Recipe::select('recipes.*')
            ->selectRaw('COUNT(DISTINCT ri.id) as common_ingredients')
            ->join('recipe_ingredients as ri', 'recipes.id', '=', 'ri.recipe_id')
            ->join('recipe_ingredients as target_ri', function($join) use ($recipeId) {
                $join->on('ri.ingredient', 'ILIKE', DB::raw("'%' || target_ri.ingredient || '%'"))
                     ->where('target_ri.recipe_id', $recipeId);
            })
            ->where('recipes.id', '!=', $recipeId)
            ->groupBy('recipes.id', 'recipes.meal_id', 'recipes.name', 'recipes.category', 
                     'recipes.area', 'recipes.thumbnail_url', 'recipes.youtube_url', 
                     'recipes.created_at', 'recipes.updated_at')
            ->orderByDesc('common_ingredients')
            ->limit($limit)
            ->get();

        return $this->formatResults($similarRecipes, 'similar_recipes', "Similar to recipe #{$recipeId}");
    }

    private function parseSearchTerms(string $query): array
    {
        $terms = preg_split('/[\s,]+/', strtolower(trim($query)));
        return array_filter($terms, fn($term) => strlen($term) >= 2);
    }

    private function buildFuzzyPattern(string $term): string
    {
        // Simple fuzzy matching pattern
        return '%' . implode('%', str_split($term)) . '%';
    }

    private function formatResults(Collection $results, string $searchType, string $query): Collection
    {
        return $results->map(function ($recipe) use ($searchType, $query) {
            $score = $recipe->relevance_score ?? $recipe->ingredient_matches ?? $recipe->common_ingredients ?? 1;
            
            return [
                'recipe_id' => $recipe->id,
                'name' => $recipe->name,
                'category' => $recipe->category,
                'area' => $recipe->area,
                'thumbnail_url' => $recipe->thumbnail_url,
                'youtube_url' => $recipe->youtube_url,
                'similarity_score' => round($score / 5.0, 4), // Normalize to 0-1 scale
                'matched_content' => $this->getMatchedContent($recipe, $searchType, $query),
                'matched_type' => $searchType,
            ];
        });
    }

    private function getMatchedContent($recipe, string $searchType, string $query): string
    {
        switch ($searchType) {
            case 'ingredients':
                // Load ingredients if needed
                if (!$recipe->relationLoaded('ingredients')) {
                    $recipe->load('ingredients');
                }
                return $recipe->ingredients->pluck('ingredient')->take(5)->implode(', ');
            case 'cuisine':
                return "{$recipe->area} cuisine, {$recipe->category} category";
            case 'dish_name':
            case 'general':
            default:
                return "{$recipe->name} - {$recipe->category} from {$recipe->area}";
        }
    }

    public function getSearchStats(): array
    {
        $totalRecipes = Recipe::count();
        
        return [
            'total_recipes' => $totalRecipes,
            'total_chunks' => 0, // Not used in SQL search
            'chunks_with_embeddings' => 0, // Not used in SQL search
            'embedding_coverage' => 0, // Not used in SQL search
            'search_method' => 'SQL-based text search',
            'capabilities' => [
                'ingredient_search' => true,
                'name_search' => true,
                'cuisine_search' => true,
                'fuzzy_matching' => true,
                'relevance_scoring' => true,
            ],
            'chunk_types' => [
                'sql_search' => $totalRecipes * 3, // Simulated for compatibility
            ],
        ];
    }
}