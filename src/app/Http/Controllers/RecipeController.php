<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RecipeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'category' => 'sometimes|string|max:100',
            'area' => 'sometimes|string|max:100',
        ]);

        $query = Recipe::with(['ingredients', 'chunks']);
        
        if ($request->has('category')) {
            $query->where('category', 'like', '%' . $request->input('category') . '%');
        }
        
        if ($request->has('area')) {
            $query->where('area', 'like', '%' . $request->input('area') . '%');
        }

        $limit = $request->input('limit', 20);
        $recipes = $query->paginate($limit);

        return response()->json($recipes);
    }

    public function show(int $id): JsonResponse
    {
        $recipe = Recipe::with(['ingredients', 'chunks'])->find($id);

        if (!$recipe) {
            return response()->json(['error' => 'Recipe not found'], 404);
        }

        return response()->json([
            'recipe' => $recipe,
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = Recipe::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json([
            'categories' => $categories,
        ]);
    }

    public function areas(): JsonResponse
    {
        $areas = Recipe::select('area')
            ->whereNotNull('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area');

        return response()->json([
            'areas' => $areas,
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total_recipes' => Recipe::count(),
            'categories' => Recipe::select('category')
                ->whereNotNull('category')
                ->groupBy('category')
                ->selectRaw('category, count(*) as count')
                ->orderByDesc('count')
                ->get()
                ->pluck('count', 'category'),
            'areas' => Recipe::select('area')
                ->whereNotNull('area')
                ->groupBy('area')
                ->selectRaw('area, count(*) as count')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'area'),
        ];

        return response()->json(['stats' => $stats]);
    }
}
