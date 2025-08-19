<?php

use App\Http\Controllers\RecipeController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Recipe CRUD endpoints
Route::prefix('recipes')->group(function () {
    Route::get('/', [RecipeController::class, 'index']);
    Route::get('/{id}', [RecipeController::class, 'show']);
    Route::get('/{recipeId}/similar', [SearchController::class, 'similarRecipes']);
});

// Recipe metadata endpoints
Route::get('/categories', [RecipeController::class, 'categories']);
Route::get('/areas', [RecipeController::class, 'areas']);
Route::get('/stats', [RecipeController::class, 'stats']);

// Recipe search endpoints
Route::prefix('search')->group(function () {
    Route::post('/ingredients', [SearchController::class, 'searchByIngredients']);
    Route::post('/cuisine', [SearchController::class, 'searchByCuisine']);
    Route::post('/dish', [SearchController::class, 'searchByDish']);
    Route::post('/general', [SearchController::class, 'generalSearch']);
    Route::post('/hybrid', [SearchController::class, 'hybridSearch']);
    Route::get('/stats', [SearchController::class, 'searchStats']);
});