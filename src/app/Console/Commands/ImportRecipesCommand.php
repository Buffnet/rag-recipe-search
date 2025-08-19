<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Models\RecipeChunk;
use App\Models\RecipeIngredient;
use App\Services\TheMealDBService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRecipesCommand extends Command
{
    protected $signature = 'recipes:import {--limit=100 : Number of recipes to import}';
    protected $description = 'Import recipes from TheMealDB API and create chunks for RAG search';

    public function __construct(
        private TheMealDBService $mealDBService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Starting import of {$limit} recipes from TheMealDB...");
        
        $importedCount = 0;
        $skippedCount = 0;
        
        $this->output->progressStart($limit);

        // Get different categories to have variety
        $categories = $this->mealDBService->getAllCategories();
        
        while ($importedCount < $limit) {
            try {
                // Get random meal
                $meal = $this->mealDBService->getRandomMeal();
                
                if (!$meal) {
                    $this->error('Failed to get random meal');
                    sleep(1);
                    continue;
                }

                // Check if already exists
                if (Recipe::where('meal_id', $meal['idMeal'])->exists()) {
                    $skippedCount++;
                    $this->output->progressAdvance();
                    continue;
                }

                // Format meal data
                $mealData = $this->mealDBService->formatMealForStorage($meal);
                
                // Create recipe with transaction
                DB::beginTransaction();
                
                $recipe = Recipe::create([
                    'meal_id' => $mealData['meal_id'],
                    'name' => $mealData['name'],
                    'category' => $mealData['category'],
                    'area' => $mealData['area'],
                    'thumbnail_url' => $mealData['thumbnail_url'],
                    'youtube_url' => $mealData['youtube_url'],
                ]);

                // Create ingredients
                foreach ($mealData['ingredients'] as $ingredientData) {
                    RecipeIngredient::create([
                        'recipe_id' => $recipe->id,
                        'ingredient' => $ingredientData['ingredient'],
                        'measure' => $ingredientData['measure'],
                    ]);
                }

                // Create chunks for RAG
                $this->createChunks($recipe, $mealData);
                
                DB::commit();
                
                $importedCount++;
                $this->output->progressAdvance();
                
                // Small delay to avoid overwhelming the API
                usleep(200000); // 200ms
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error importing recipe: {$e->getMessage()}");
                
                // Small delay before retry
                sleep(1);
            }
        }

        $this->output->progressFinish();
        
        $this->info("Import completed!");
        $this->info("Imported: {$importedCount} recipes");
        $this->info("Skipped (already exists): {$skippedCount} recipes");
        $this->info("Total chunks created: " . RecipeChunk::count());

        return self::SUCCESS;
    }

    private function createChunks(Recipe $recipe, array $mealData): void
    {
        // Chunk 1: Title and metadata
        $titleMetaContent = "{$recipe->name}. Category: {$recipe->category}. Cuisine: {$recipe->area}";
        RecipeChunk::create([
            'recipe_id' => $recipe->id,
            'chunk_type' => RecipeChunk::CHUNK_TYPE_TITLE_META,
            'content' => $titleMetaContent,
        ]);

        // Chunk 2: Ingredients list
        $ingredientsList = collect($mealData['ingredients'])
            ->pluck('ingredient')
            ->join(', ');
        
        RecipeChunk::create([
            'recipe_id' => $recipe->id,
            'chunk_type' => RecipeChunk::CHUNK_TYPE_INGREDIENTS,
            'content' => $ingredientsList,
        ]);

        // Chunk 3: Instructions (if available)
        if (!empty($mealData['instructions'])) {
            RecipeChunk::create([
                'recipe_id' => $recipe->id,
                'chunk_type' => RecipeChunk::CHUNK_TYPE_INSTRUCTIONS,
                'content' => trim($mealData['instructions']),
            ]);
        }
    }
}
