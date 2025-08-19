<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebSearchController;
use App\Http\Controllers\WebRecipeController;

// Main search interface
Route::get('/', [WebSearchController::class, 'index'])->name('search.index');

// Recipe details page
Route::get('/recipe/{id}', [WebRecipeController::class, 'show'])->name('recipe.show');
