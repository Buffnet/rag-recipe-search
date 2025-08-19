<?php

namespace App\Http\Controllers;

use App\Services\RecipeSearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        private RecipeSearchService $searchService
    ) {}

    public function searchByIngredients(Request $request): JsonResponse
    {
        $request->validate([
            'ingredients' => 'required|string|max:500',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $ingredients = $request->input('ingredients');
        $limit = $request->input('limit', 10);

        $results = $this->searchService->searchByIngredients($ingredients, $limit);

        return response()->json([
            'query' => $ingredients,
            'search_type' => 'ingredients',
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    public function searchByCuisine(Request $request): JsonResponse
    {
        $request->validate([
            'cuisine' => 'required|string|max:200',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $cuisine = $request->input('cuisine');
        $limit = $request->input('limit', 10);

        $results = $this->searchService->searchByCuisine($cuisine, $limit);

        return response()->json([
            'query' => $cuisine,
            'search_type' => 'cuisine',
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    public function searchByDish(Request $request): JsonResponse
    {
        $request->validate([
            'dish' => 'required|string|max:200',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $dish = $request->input('dish');
        $limit = $request->input('limit', 10);

        $results = $this->searchService->searchByDishName($dish, $limit);

        return response()->json([
            'query' => $dish,
            'search_type' => 'dish_name',
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    public function generalSearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|max:500',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 10);

        $results = $this->searchService->generalSearch($query, $limit);

        return response()->json([
            'query' => $query,
            'search_type' => 'general',
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    public function hybridSearch(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|max:500',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = $request->input('q');
        $limit = $request->input('limit', 10);

        $results = $this->searchService->hybridSearch($query, $limit);

        return response()->json([
            'query' => $query,
            'search_type' => 'hybrid',
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    public function similarRecipes(Request $request, int $recipeId): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:20',
        ]);

        $limit = $request->input('limit', 5);
        $results = $this->searchService->findSimilarRecipes($recipeId, $limit);

        return response()->json([
            'recipe_id' => $recipeId,
            'search_type' => 'similar_recipes',
            'results' => $results,
            'count' => $results->count(),
        ]);
    }

    public function searchStats(): JsonResponse
    {
        $stats = $this->searchService->getSearchStats();

        return response()->json([
            'stats' => $stats,
        ]);
    }
}
