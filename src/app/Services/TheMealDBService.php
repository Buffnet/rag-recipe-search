<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TheMealDBService
{
    private const BASE_URL = 'https://www.themealdb.com/api/json/v1/1';

    public function searchByName(string $name): array
    {
        $response = Http::get(self::BASE_URL . '/search.php', ['s' => $name]);
        
        if (!$response->successful()) {
            Log::error('TheMealDB API error', [
                'url' => $response->transferStats->getRequest()->getUri(),
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return [];
        }

        $data = $response->json();
        return $data['meals'] ?? [];
    }

    public function getRandomMeal(): ?array
    {
        $response = Http::get(self::BASE_URL . '/random.php');
        
        if (!$response->successful()) {
            Log::error('TheMealDB API error for random meal', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return null;
        }

        $data = $response->json();
        return $data['meals'][0] ?? null;
    }

    public function getAllCategories(): array
    {
        $response = Http::get(self::BASE_URL . '/categories.php');
        
        if (!$response->successful()) {
            Log::error('TheMealDB API error for categories', [
                'status' => $response->status()
            ]);
            return [];
        }

        $data = $response->json();
        return $data['categories'] ?? [];
    }

    public function getAllAreas(): array
    {
        $response = Http::get(self::BASE_URL . '/list.php', ['a' => 'list']);
        
        if (!$response->successful()) {
            Log::error('TheMealDB API error for areas', [
                'status' => $response->status()
            ]);
            return [];
        }

        $data = $response->json();
        return $data['meals'] ?? [];
    }

    public function getMealsByCategory(string $category): array
    {
        $response = Http::get(self::BASE_URL . '/filter.php', ['c' => $category]);
        
        if (!$response->successful()) {
            Log::error('TheMealDB API error for category filter', [
                'category' => $category,
                'status' => $response->status()
            ]);
            return [];
        }

        $data = $response->json();
        return $data['meals'] ?? [];
    }

    public function getMealsByArea(string $area): array
    {
        $response = Http::get(self::BASE_URL . '/filter.php', ['a' => $area]);
        
        if (!$response->successful()) {
            Log::error('TheMealDB API error for area filter', [
                'area' => $area,
                'status' => $response->status()
            ]);
            return [];
        }

        $data = $response->json();
        return $data['meals'] ?? [];
    }

    public function getMealById(string $id): ?array
    {
        $response = Http::get(self::BASE_URL . '/lookup.php', ['i' => $id]);
        
        if (!$response->successful()) {
            Log::error('TheMealDB API error for meal lookup', [
                'id' => $id,
                'status' => $response->status()
            ]);
            return null;
        }

        $data = $response->json();
        return $data['meals'][0] ?? null;
    }

    public function extractIngredients(array $meal): array
    {
        $ingredients = [];
        
        for ($i = 1; $i <= 20; $i++) {
            $ingredient = trim($meal["strIngredient{$i}"] ?? '');
            $measure = trim($meal["strMeasure{$i}"] ?? '');
            
            if (!empty($ingredient)) {
                $ingredients[] = [
                    'ingredient' => $ingredient,
                    'measure' => $measure ?: null,
                ];
            }
        }
        
        return $ingredients;
    }

    public function formatMealForStorage(array $meal): array
    {
        return [
            'meal_id' => $meal['idMeal'],
            'name' => $meal['strMeal'],
            'category' => $meal['strCategory'] ?? null,
            'area' => $meal['strArea'] ?? null,
            'thumbnail_url' => $meal['strMealThumb'] ?? null,
            'youtube_url' => $meal['strYoutube'] ?? null,
            'instructions' => $meal['strInstructions'] ?? '',
            'ingredients' => $this->extractIngredients($meal),
        ];
    }
}