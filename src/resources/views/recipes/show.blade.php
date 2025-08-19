@extends('layouts.app')

@section('title', $recipe->name . ' - Recipe Details')

@section('content')
<div style="margin-bottom: 20px;">
    <a href="{{ route('search.index') }}" style="
        display: inline-flex; 
        align-items: center; 
        color: white; 
        text-decoration: none; 
        background: rgba(255,255,255,0.2); 
        padding: 8px 16px; 
        border-radius: 20px;
        transition: background 0.3s ease;
    " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
        ← Back to Search
    </a>
</div>

<div class="search-container">
    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px; align-items: start;">
        <!-- Recipe Image & Basic Info -->
        <div>
            <img src="{{ $recipe->thumbnail_url }}" alt="{{ $recipe->name }}" 
                 style="width: 100%; border-radius: 10px; margin-bottom: 20px;"
                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 400 300%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22400%22 height=%22300%22/%3E%3Ctext fill=%22%23999%22 x=%22200%22 y=%22150%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Image Available%3C/text%3E%3C/svg%3E'">
            
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                <h3 style="margin-bottom: 15px; color: #333;">📊 Recipe Info</h3>
                <div style="display: grid; gap: 8px;">
                    <div><strong>Category:</strong> {{ $recipe->category }}</div>
                    <div><strong>Cuisine:</strong> {{ $recipe->area }}</div>
                    @if($recipe->youtube_url)
                        <div style="margin-top: 15px;">
                            <a href="{{ $recipe->youtube_url }}" target="_blank" 
                               style="display: inline-flex; align-items: center; background: #ff0000; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none;">
                                📺 Watch Video
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Recipe Details -->
        <div>
            <h1 style="font-size: 2.5rem; margin-bottom: 20px; color: #333;">{{ $recipe->name }}</h1>
            
            <!-- Ingredients Section -->
            <div style="margin-bottom: 30px;">
                <h2 style="color: #667eea; margin-bottom: 15px; display: flex; align-items: center;">
                    🥘 Ingredients
                </h2>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                    @if($recipe->ingredients->count() > 0)
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            @foreach($recipe->ingredients as $ingredient)
                                <li style="padding: 8px 0; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between;">
                                    <span>{{ $ingredient->ingredient }}</span>
                                    @if($ingredient->measure)
                                        <span style="color: #666; font-weight: 500;">{{ $ingredient->measure }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p style="color: #666; font-style: italic;">No ingredients listed</p>
                    @endif
                </div>
            </div>
            
            <!-- Instructions Section -->
            @php
                $instructionsChunk = $recipe->chunks->where('chunk_type', 'instructions')->first();
            @endphp
            
            @if($instructionsChunk)
                <div>
                    <h2 style="color: #667eea; margin-bottom: 15px; display: flex; align-items: center;">
                        👨‍🍳 Instructions
                    </h2>
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; line-height: 1.8;">
                        {!! nl2br(e($instructionsChunk->content)) !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Similar Recipes Section -->
<div class="search-container" style="margin-top: 30px;">
    <h2 style="color: #667eea; margin-bottom: 20px; display: flex; align-items: center;">
        🔍 Similar Recipes
    </h2>
    <div id="similar-recipes" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
        <div style="text-align: center; padding: 20px; color: #666;">
            <p>🔍 Loading similar recipes...</p>
        </div>
    </div>
</div>

<style>
    @media (max-width: 768px) {
        .search-container > div:first-child {
            grid-template-columns: 1fr !important;
            gap: 20px !important;
        }
        
        h1 {
            font-size: 2rem !important;
        }
        
        #similar-recipes {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load similar recipes
    loadSimilarRecipes({{ $recipe->id }});
    
    async function loadSimilarRecipes(recipeId) {
        try {
            const response = await fetch(`/api/search/similar/${recipeId}`, {
                method: 'POST',
                headers: window.defaultHeaders,
                body: JSON.stringify({ limit: 4 })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            displaySimilarRecipes(result.results);
            
        } catch (error) {
            console.error('Error loading similar recipes:', error);
            document.getElementById('similar-recipes').innerHTML = `
                <div style="text-align: center; padding: 20px; color: #d32f2f;">
                    <p>😕 Could not load similar recipes</p>
                </div>
            `;
        }
    }
    
    function displaySimilarRecipes(recipes) {
        const container = document.getElementById('similar-recipes');
        
        if (!recipes || recipes.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #666;">
                    <p>No similar recipes found</p>
                </div>
            `;
            return;
        }
        
        const recipesHTML = recipes.map(recipe => `
            <div class="recipe-card" onclick="window.location.href='/recipe/${recipe.recipe_id}'" 
                 style="background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.3s ease;">
                <img src="${recipe.thumbnail_url}" alt="${recipe.name}" 
                     style="width: 100%; height: 150px; object-fit: cover;"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 300 150%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22300%22 height=%22150%22/%3E%3Ctext fill=%22%23999%22 x=%22150%22 y=%2275%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                <div style="padding: 15px;">
                    <h4 style="margin: 0 0 8px 0; color: #333; font-size: 1rem;">${recipe.name}</h4>
                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.8rem; color: #666;">
                        <span>${recipe.area}</span>
                        <span style="background: #667eea; color: white; padding: 2px 6px; border-radius: 8px;">
                            ${Math.round(recipe.similarity_score * 100)}% match
                        </span>
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = recipesHTML;
        
        // Add hover effects
        container.querySelectorAll('.recipe-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }
});
</script>
@endsection