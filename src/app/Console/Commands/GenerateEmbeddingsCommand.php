<?php

namespace App\Console\Commands;

use App\Models\RecipeChunk;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateEmbeddingsCommand extends Command
{
    protected $signature = 'embeddings:generate {--batch-size=10 : Number of chunks to process in each batch}';
    protected $description = 'Generate embeddings for recipe chunks using OpenAI API';

    public function __construct(
        private EmbeddingService $embeddingService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting embeddings generation...');

        // Check if OpenAI API key is configured
        try {
            $testEmbedding = $this->embeddingService->generateEmbedding('test');
            if (!$testEmbedding) {
                $this->error('Failed to generate test embedding. Check your OpenAI API key.');
                return self::FAILURE;
            }
            $this->info('✓ OpenAI API connection verified');
        } catch (\Exception $e) {
            $this->error('OpenAI API error: ' . $e->getMessage());
            $this->info('Please set your OpenAI API key in the .env file:');
            $this->info('OPENAI_API_KEY=your_key_here');
            return self::FAILURE;
        }

        $batchSize = (int) $this->option('batch-size');
        
        // Get chunks without embeddings
        $chunksWithoutEmbeddings = RecipeChunk::whereNull('embedding')->count();
        
        if ($chunksWithoutEmbeddings === 0) {
            $this->info('All chunks already have embeddings!');
            return self::SUCCESS;
        }

        $this->info("Found {$chunksWithoutEmbeddings} chunks without embeddings");
        
        $this->output->progressStart($chunksWithoutEmbeddings);

        $processedCount = 0;
        $errorCount = 0;

        RecipeChunk::whereNull('embedding')
            ->chunk($batchSize, function ($chunks) use (&$processedCount, &$errorCount) {
                foreach ($chunks as $chunk) {
                    try {
                        $embedding = $this->embeddingService->generateEmbedding($chunk->content);
                        
                        if ($embedding) {
                            // Update chunk with embedding using raw SQL to handle vector type
                            DB::statement(
                                'UPDATE recipe_chunks SET embedding = ?::vector WHERE id = ?',
                                [$this->embeddingService->formatVectorForPostgres($embedding), $chunk->id]
                            );
                            $processedCount++;
                        } else {
                            $this->warn("Failed to generate embedding for chunk {$chunk->id}");
                            $errorCount++;
                        }
                        
                        $this->output->progressAdvance();
                        
                        // Small delay to respect rate limits
                        usleep(100000); // 100ms
                        
                    } catch (\Exception $e) {
                        $this->error("Error processing chunk {$chunk->id}: {$e->getMessage()}");
                        $errorCount++;
                        $this->output->progressAdvance();
                    }
                }
            });

        $this->output->progressFinish();
        
        $this->info("Embeddings generation completed!");
        $this->info("Successfully processed: {$processedCount} chunks");
        
        if ($errorCount > 0) {
            $this->warn("Failed to process: {$errorCount} chunks");
        }

        // Verify embeddings were created
        $totalWithEmbeddings = RecipeChunk::whereNotNull('embedding')->count();
        $this->info("Total chunks with embeddings: {$totalWithEmbeddings}");

        return self::SUCCESS;
    }
}
