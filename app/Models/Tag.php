<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasTranslationFallbacks;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;
    use HasContentStatus;
    use HasTranslationFallbacks;
    use Translatable;

    public array $translatedAttributes = [
        'name',
        'slug',
        'description',
        'seo_title',
        'seo_description',
    ];

    protected $fillable = [
        'status',
    ];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withTimestamps();
    }

    public function nameForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('name', $locale);
    }

    public function slugForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('slug', $locale);
    }

    public function seoTitleForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('seo_title', $locale) ?: $this->nameForLocale($locale);
    }

    public function seoDescriptionForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('seo_description', $locale) ?: $this->translatedValue('description', $locale);
    }
}
