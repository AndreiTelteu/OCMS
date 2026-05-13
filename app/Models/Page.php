<?php

namespace App\Models;

use App\Models\Concerns\HasContentStatus;
use App\Models\Concerns\HasTranslationFallbacks;
use Astrotomic\Translatable\Translatable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasContentStatus;

    /** @use HasFactory<PageFactory> */
    use HasFactory;
    use HasTranslationFallbacks;
    use Translatable;

    public array $translatedAttributes = [
        'title',
        'slug',
        'body',
        'seo_title',
        'seo_description',
    ];

    protected $fillable = [
        'status',
        'template',
        'is_home',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_home' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function descriptionForLocale(?string $locale = null): ?string
    {
        return $this->seoDescriptionForLocale($locale) ?: $this->bodyForLocale($locale);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order');
    }

    public function localizedRoutes(): HasMany
    {
        return $this->hasMany(LocalizedRoute::class, 'routable_id')
            ->where('routable_type', self::class);
    }

    public function titleForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('title', $locale);
    }

    public function slugForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('slug', $locale);
    }

    public function rootPathForLocale(?string $locale = null): string
    {
        if ($this->is_home) {
            return '';
        }

        return trim((string) $this->slugForLocale($locale), '/');
    }

    public function seoTitleForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('seo_title', $locale) ?: $this->titleForLocale($locale);
    }

    public function seoDescriptionForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('seo_description', $locale);
    }

    public function bodyForLocale(?string $locale = null): ?string
    {
        return $this->translatedValue('body', $locale);
    }
}
