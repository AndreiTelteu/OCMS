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

    public function switcherForCurrentRoute(): array
    {
        return collect(config('cms.supported_locales'))
            ->mapWithKeys(fn (string $locale) => [$locale => Route::localizedSwitcherUrl($locale)])
            ->all();
    }

    public function alternatesForPage(Page $page): array
    {
        return $this->alternates(fn (string $locale): string => $this->page($page, $locale));
    }

    public function switcherForPage(Page $page): array
    {
        return $this->alternates(fn (string $locale): string => $this->page($page, $locale, true));
    }

    public function alternatesForArticle(Article $article): array
    {
        return $this->alternates(fn (string $locale): string => $this->article($article, $locale));
    }

    public function switcherForArticle(Article $article): array
    {
        return $this->alternates(fn (string $locale): string => $this->article($article, $locale, true));
    }

    public function alternatesForCategory(Category $category): array
    {
        return $this->alternates(fn (string $locale): string => $this->category($category, $locale));
    }

    public function switcherForCategory(Category $category): array
    {
        return $this->alternates(fn (string $locale): string => $this->category($category, $locale, true));
    }

    public function alternatesForTag(Tag $tag): array
    {
        return $this->alternates(fn (string $locale): string => $this->tag($tag, $locale));
    }

    public function switcherForTag(Tag $tag): array
    {
        return $this->alternates(fn (string $locale): string => $this->tag($tag, $locale, true));
    }

    public function page(Page $page, ?string $locale = null, bool $switcher = false): string
    {
        $locale ??= app()->getLocale();

        if ($page->is_home) {
            return $this->localizedRoute('home', [], $locale, $switcher);
        }

        return $this->localizedRoute('content.root', [
            'path' => $page->rootPathForLocale($locale),
        ], $locale, $switcher);
    }

    public function article(Article $article, ?string $locale = null, bool $switcher = false): string
    {
        $locale ??= app()->getLocale();

        return $this->localizedRoute('content.root', [
            'path' => $article->slugForLocale($locale) ?? '',
        ], $locale, $switcher);
    }

    public function category(Category $category, ?string $locale = null, bool $switcher = false): string
    {
        $locale ??= app()->getLocale();

        return $this->translatedRoute('category.show', [
            'path' => trim($category->pathForLocale($locale) ?? '', '/'),
        ], $locale, $switcher);
    }

    public function tag(Tag $tag, ?string $locale = null, bool $switcher = false): string
    {
        $locale ??= app()->getLocale();

        return $this->translatedRoute('tag.show', [
            'slug' => trim($tag->slugForLocale($locale) ?? '', '/'),
        ], $locale, $switcher);
    }

    private function localizedRoute(string $name, array $parameters, string $locale, bool $switcher): string
    {
        $routeName = $this->localizedRouteName($name, $locale, $switcher);

        if (str_starts_with($routeName, 'with_locale.')) {
            $parameters['locale'] = $locale;
        }

        return route($routeName, $parameters);
    }

    private function translatedRoute(string $name, array $parameters, string $locale, bool $switcher): string
    {
        return route($this->translatedRouteName($name, $locale, $switcher), $parameters);
    }

    private function localizedRouteName(string $name, string $locale, bool $switcher): string
    {
        if ($this->usesWithoutLocaleVariant($locale, $switcher)) {
            return 'without_locale.'.$name;
        }

        return 'with_locale.'.$name;
    }

    private function translatedRouteName(string $name, string $locale, bool $switcher): string
    {
        if ($this->usesWithoutLocaleVariant($locale, $switcher)) {
            return 'without_locale.'.$name;
        }

        return "translated_{$locale}.{$name}";
    }

    private function usesWithoutLocaleVariant(string $locale, bool $switcher): bool
    {
        return ! $switcher
            && Localizer::hideDefaultLocale()
            && $locale === config('app.fallback_locale');
    }

    private function alternates(callable $urlResolver): array
    {
        return collect(config('cms.supported_locales'))
            ->mapWithKeys(fn (string $locale): array => [$locale => $urlResolver($locale)])
            ->all();
    }
}
