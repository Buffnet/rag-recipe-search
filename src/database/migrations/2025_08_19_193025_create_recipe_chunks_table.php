<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('recipe_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained('recipes')->onDelete('cascade');
            $table->string('chunk_type'); // 'title_meta', 'ingredients', 'instructions'
            $table->text('content');
            $table->timestamps();
            
            // Add vector column using raw SQL
            $table->index('chunk_type');
        });
        
        // Add vector column for embeddings (1536 dimensions for OpenAI text-embedding-3-small)
        DB::statement('ALTER TABLE recipe_chunks ADD COLUMN embedding vector(1536)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_chunks');
    }
};
