<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\Cms\SchemaBuilder;
use App\Services\Cms\SeoData;

class TagController extends Controller
{
    public function __construct(private readonly SeoData $seo, private readonly SchemaBuilder $schema)
    {
    }

    public function __invoke(string $slug)
    {
        $locale = app()->getLocale();
        $fallbackLocale = config('cms.fallback_locale');
        $slug = trim($slug, '/');

        $tag = Tag::query()
            ->where(function ($query) use ($fallbackLocale, $locale, $slug): void {
                $query->whereTranslation('slug', $slug, $locale);

                if ($locale !== $fallbackLocale) {
                    $query->orWhere(function ($query) use ($fallbackLocale, $locale, $slug): void {
                        $query->whereDoesntHave('translations', function ($translationQuery) use ($locale): void {
                            $translationQuery->where('locale', $locale);
                        })->whereTranslation('slug', $slug, $fallbackLocale);
                    });
                }
            })
            ->firstOrFail();

        return view('cms.content', [
            'model' => $tag,
            'seo' => $this->seo->forTag($tag),
            'schema' => $this->schema->forTag($tag),
        ]);
    }
}
