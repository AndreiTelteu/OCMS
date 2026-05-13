<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryTranslation extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'locale',
        'name',
        'slug',
        'path',
        'description',
        'seo_title',
        'seo_description',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
