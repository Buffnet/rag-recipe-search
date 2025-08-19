<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = [
        'meal_id',
        'name',
        'category',
        'area',
        'thumbnail_url',
        'youtube_url',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(RecipeChunk::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }
}
