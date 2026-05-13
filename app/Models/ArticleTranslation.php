<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleTranslation extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleTranslationFactory> */
    use HasFactory;

    protected $fillable = [
        'article_id',
        'locale',
        'title',
        'slug',
        'excerpt',
        'body',
        'seo_title',
        'seo_description',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
