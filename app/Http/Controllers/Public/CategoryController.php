<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Cms\SchemaBuilder;
use App\Services\Cms\SeoData;

class CategoryController extends Controller
{
    public function __construct(private readonly SeoData $seo, private readonly SchemaBuilder $schema)
    {
    }

    public function __invoke(string $path)
    {
        $locale = app()->getLocale();
        $fallbackLocale = config('cms.fallback_locale');
        $path = trim($path, '/');

        $category = Category::query()
            ->where(function ($query) use ($fallbackLocale, $locale, $path): void {
                $query->whereTranslation('path', $path, $locale);

                if ($locale !== $fallbackLocale) {
                    $query->orWhere(function ($query) use ($fallbackLocale, $locale, $path): void {
                        $query->whereDoesntHave('translations', function ($translationQuery) use ($locale): void {
                            $translationQuery->where('locale', $locale);
                        })->whereTranslation('path', $path, $fallbackLocale);
                    });
                }
            })
            ->firstOrFail();

        return view('cms.content', [
            'model' => $category,
            'seo' => $this->seo->forCategory($category),
            'schema' => $this->schema->forCategory($category),
        ]);
    }
}
