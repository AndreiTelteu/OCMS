<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Support\Facades\Route;
use NielsNumbers\LaravelLocalizer\Facades\Localizer;

class LocalizedUrlGenerator
{
    public function __construct(private readonly LocalizedRouteRegistry $routes)
    {
    }

    public function canonicalForCurrentRoute(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return Route::localizedUrl($locale);
    }

    public function localizedSwitcherForCurrentRoute(string $locale): string
    {
        return Route::localizedSwitcherUrl($locale);
    }

    public function alternatesForCurrentRoute(): array
    {
        return collect(config('cms.supported_locales'))
            ->mapWithKeys(fn (string $locale) => [$locale => Route::localizedUrl($locale)])
            ->all();
    }

    public function page(Page $page, ?string $locale = null, bool $switcher = false): string
    {
        return $this->rootUrl($page->slugForLocale($locale ?? app()->getLocale()) ?? '', $locale, $switcher);
    }

    public function article(Article $article, ?string $locale = null, bool $switcher = false): string
    {
        return $this->rootUrl($article->slugForLocale($locale ?? app()->getLocale()) ?? '', $locale, $switcher);
    }

    public function category(Category $category, ?string $locale = null, bool $switcher = false): string
    {
        $locale ??= app()->getLocale();
        $pattern = trim(Localizer::url('category/{path}', $locale), '/');
        $path = trim($category->pathForLocale($locale) ?? '', '/');

        return $this->prefixedUrl(str_replace('{path}', $path, $pattern), $locale, $switcher);
    }

    public function tag(Tag $tag, ?string $locale = null, bool $switcher = false): string
    {
        $locale ??= app()->getLocale();
        $pattern = trim(Localizer::url('tag/{slug}', $locale), '/');
        $slug = trim($tag->slugForLocale($locale) ?? '', '/');

        return $this->prefixedUrl(str_replace('{slug}', $slug, $pattern), $locale, $switcher);
    }

    private function rootUrl(string $path, ?string $locale = null, bool $switcher = false): string
    {
        $locale ??= app()->getLocale();

        return $this->prefixedUrl(trim($path, '/'), $locale, $switcher);
    }

    private function prefixedUrl(string $path, string $locale, bool $switcher): string
    {
        $default = config('app.fallback_locale');
        $needsPrefix = $switcher || config('cms.main_lang_prefix') || $locale !== $default;
        $prefix = $needsPrefix ? '/'.$locale : '';

        return url(trim($prefix.'/'.$path, '/'));
    }
}
