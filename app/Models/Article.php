<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasTranslationFallbacks;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;
    use HasContentStatus;
    use HasTranslationFallbacks;
    use Translatable;

    public array $translatedAttributes = [
        'title',
        'slug',
        'excerpt',
        'body',
        'seo_title',
        'seo_description',
    ];

    protected $fillable = [
        'status',
        'published_at',
        'author_id',
        'featured_image_path',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function titleForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('title', $locale);
    }

    public function slugForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('slug', $locale);
    }

    public function excerptForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('excerpt', $locale);
    }

    public function bodyForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('body', $locale);
    }

    public function seoTitleForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('seo_title', $locale) ?: $this->titleForLocale($locale);
    }

    public function seoDescriptionForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('seo_description', $locale) ?: $this->excerptForLocale($locale);
    }
}
