<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebRecipeController extends Controller
{
    public function show(int $id): View
    {
        $recipe = Recipe::with(['ingredients', 'chunks'])->findOrFail($id);
        
        return view('recipes.show', compact('recipe'));
    }
}