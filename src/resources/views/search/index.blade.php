@extends('layouts.app')

@section('title', 'Recipe Search - Find Your Perfect Meal')

@section('content')
<div class="search-container">
    <!-- Search Tabs -->
    <div class="search-tabs">
        <button class="tab active" data-tab="ingredients">🥕 By Ingredients</button>
        <button class="tab" data-tab="cuisine">🌍 By Cuisine</button>
        <button class="tab" data-tab="dish">🍽️ By Dish Name</button>
        <button class="tab" data-tab="general">🔍 General Search</button>
    </div>
    
    <!-- Ingredients Search -->
    <form class="search-form active" id="ingredients-form" data-endpoint="/api/search/ingredients">
        <div class="form-group">
            <label for="ingredients-input">What ingredients do you have?</label>
            <input type="text" id="ingredients-input" name="ingredients" 
                   placeholder="e.g., chicken, rice, tomatoes" 
                   autocomplete="off">
            <small style="color: #666; font-size: 0.9rem;">Separate ingredients with commas</small>
        </div>
        <button type="submit" class="search-btn">🔍 Find Recipes</button>
    </form>
    
    <!-- Cuisine Search -->
    <form class="search-form" id="cuisine-form" data-endpoint="/api/search/cuisine">
        <div class="form-group">
            <label for="cuisine-input">What cuisine are you craving?</label>
            <input type="text" id="cuisine-input" name="cuisine" 
                   placeholder="e.g., Italian, Japanese, Mexican" 
                   autocomplete="off">
        </div>
        <button type="submit" class="search-btn">🔍 Find Recipes</button>
    </form>
    
    <!-- Dish Name Search -->
    <form class="search-form" id="dish-form" data-endpoint="/api/search/dish">
        <div class="form-group">
            <label for="dish-input">Looking for a specific dish?</label>
            <input type="text" id="dish-input" name="dish" 
                   placeholder="e.g., pasta, curry, salad" 
                   autocomplete="off">
        </div>
        <button type="submit" class="search-btn">🔍 Find Recipes</button>
    </form>
    
    <!-- General Search -->
    <form class="search-form" id="general-form" data-endpoint="/api/search/general">
        <div class="form-group">
            <label for="general-input">Describe what you want to cook</label>
            <textarea id="general-input" name="q" rows="3" 
                     placeholder="e.g., spicy chicken recipe with vegetables"></textarea>
        </div>
        <button type="submit" class="search-btn">🔍 Find Recipes</button>
    </form>
</div>

<!-- Loading State -->
<div class="loading" id="loading">
    <p>🔍 Searching for delicious recipes...</p>
</div>

<!-- Search Results -->
<div class="results" id="results"></div>

<!-- No Results Message -->
<div id="no-results" style="display: none; text-align: center; padding: 40px; color: #666;">
    <h3>😔 No recipes found</h3>
    <p>Try different ingredients or search terms</p>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabs = document.querySelectorAll('.tab');
    const forms = document.querySelectorAll('.search-form');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            
            // Update active tab
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show corresponding form
            forms.forEach(form => {
                form.classList.remove('active');
                if (form.id === `${tabName}-form`) {
                    form.classList.add('active');
                }
            });
        });
    });
    
    // Search functionality
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch(this);
        });
    });
    
    async function performSearch(form) {
        const endpoint = form.dataset.endpoint;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Show loading state
        document.getElementById('loading').style.display = 'block';
        document.getElementById('results').innerHTML = '';
        document.getElementById('no-results').style.display = 'none';
        
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: window.defaultHeaders,
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            displayResults(result.results);
            
        } catch (error) {
            console.error('Search error:', error);
            document.getElementById('results').innerHTML = `
                <div style="text-align: center; padding: 20px; color: #d32f2f;">
                    <h3>😕 Something went wrong</h3>
                    <p>Please try again in a moment</p>
                </div>
            `;
        } finally {
            document.getElementById('loading').style.display = 'none';
        }
    }
    
    function displayResults(recipes) {
        const resultsContainer = document.getElementById('results');
        
        if (!recipes || recipes.length === 0) {
            document.getElementById('no-results').style.display = 'block';
            return;
        }
        
        const recipesHTML = recipes.map(recipe => `
            <div class="recipe-card" onclick="window.location.href='/recipe/${recipe.recipe_id}'">
                <img src="${recipe.thumbnail_url}" alt="${recipe.name}" class="recipe-image" 
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 300 200%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22300%22 height=%22200%22/%3E%3Ctext fill=%22%23999%22 x=%22150%22 y=%22100%22 text-anchor=%22middle%22 dy=%22.3em%22%3ENo Image%3C/text%3E%3C/svg%3E'">
                <div class="recipe-content">
                    <h3 class="recipe-title">${recipe.name}</h3>
                    <div class="recipe-meta">
                        <span>${recipe.area} • ${recipe.category}</span>
                        <span class="recipe-score">${Math.round(recipe.similarity_score * 100)}% match</span>
                    </div>
                    <p class="recipe-matched">${recipe.matched_content}</p>
                </div>
            </div>
        `).join('');
        
        resultsContainer.innerHTML = recipesHTML;
    }
    
    // Auto-focus on the active search input
    function focusActiveInput() {
        const activeForm = document.querySelector('.search-form.active');
        if (activeForm) {
            const input = activeForm.querySelector('input, textarea');
            if (input) input.focus();
        }
    }
    
    // Focus on initial load
    focusActiveInput();
    
    // Focus when tab changes
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            setTimeout(focusActiveInput, 100);
        });
    });
});
</script>
@endsection