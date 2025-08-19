<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EmbeddingService
{
    private const OPENAI_API_URL = 'https://api.openai.com/v1/embeddings';
    private const MODEL = 'text-embedding-3-small';
    private const DIMENSIONS = 1536;
    private const CACHE_TTL = 86400; // 24 hours

    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('app.openai_api_key') ?? env('OPENAI_API_KEY');
        
        if (!$this->apiKey) {
            throw new \Exception('OpenAI API key is not configured');
        }
    }

    public function generateEmbedding(string $text): ?array
    {
        // Create cache key based on text content
        $cacheKey = 'embedding_' . md5($text);
        
        // Try to get from cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post(self::OPENAI_API_URL, [
                'model' => self::MODEL,
                'input' => $text,
                'dimensions' => self::DIMENSIONS,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'text_length' => strlen($text),
                ]);
                return null;
            }

            $data = $response->json();
            $embedding = $data['data'][0]['embedding'] ?? null;

            if ($embedding) {
                // Cache the result
                Cache::put($cacheKey, $embedding, self::CACHE_TTL);
            }

            return $embedding;

        } catch (\Exception $e) {
            Log::error('Embedding generation failed', [
                'error' => $e->getMessage(),
                'text_length' => strlen($text),
            ]);
            return null;
        }
    }

    public function generateEmbeddings(array $texts): array
    {
        $embeddings = [];
        
        foreach ($texts as $index => $text) {
            $embedding = $this->generateEmbedding($text);
            $embeddings[$index] = $embedding;
            
            // Small delay to avoid rate limiting
            if (count($texts) > 1) {
                usleep(100000); // 100ms
            }
        }
        
        return $embeddings;
    }

    public function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== count($vectorB)) {
            throw new \InvalidArgumentException('Vectors must have the same length');
        }

        $dotProduct = 0;
        $magnitudeA = 0;
        $magnitudeB = 0;

        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $magnitudeA += $vectorA[$i] * $vectorA[$i];
            $magnitudeB += $vectorB[$i] * $vectorB[$i];
        }

        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);

        if ($magnitudeA == 0 || $magnitudeB == 0) {
            return 0;
        }

        return $dotProduct / ($magnitudeA * $magnitudeB);
    }

    public function formatVectorForPostgres(array $vector): string
    {
        return '[' . implode(',', $vector) . ']';
    }

    public function parseVectorFromPostgres(?string $vectorString): ?array
    {
        if (!$vectorString) {
            return null;
        }

        // Remove brackets and split by comma
        $vectorString = trim($vectorString, '[]');
        $values = explode(',', $vectorString);
        
        return array_map('floatval', $values);
    }
}