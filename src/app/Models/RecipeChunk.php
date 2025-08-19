<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeChunk extends Model
{
    protected $fillable = [
        'recipe_id',
        'chunk_type',
        'content',
        'embedding',
    ];

    protected $casts = [
        'embedding' => 'array',
    ];

    public const CHUNK_TYPE_TITLE_META = 'title_meta';
    public const CHUNK_TYPE_INGREDIENTS = 'ingredients';
    public const CHUNK_TYPE_INSTRUCTIONS = 'instructions';

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
