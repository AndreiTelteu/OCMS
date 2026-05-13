<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use Spatie\SchemaOrg\Schema;

class SchemaBuilder
{
    public function forPage(Page $page, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        return Schema::webPage()
            ->name($page->seoTitleForLocale($locale) ?? $page->titleForLocale($locale))
            ->description($page->seoDescriptionForLocale($locale))
            ->url(app(LocalizedUrlGenerator::class)->page($page, $locale))
            ->toScript();
    }

    public function forArticle(Article $article, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        return Schema::article()
            ->headline($article->seoTitleForLocale($locale) ?? $article->titleForLocale($locale))
            ->description($article->seoDescriptionForLocale($locale))
            ->url(app(LocalizedUrlGenerator::class)->article($article, $locale))
            ->toScript();
    }

    public function forCategory(Category $category, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        return Schema::collectionPage()
            ->name($category->seoTitleForLocale($locale))
            ->description($category->seoDescriptionForLocale($locale))
            ->url(app(LocalizedUrlGenerator::class)->category($category, $locale))
            ->toScript();
    }

    public function forTag(Tag $tag, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        return Schema::collectionPage()
            ->name($tag->seoTitleForLocale($locale))
            ->description($tag->seoDescriptionForLocale($locale))
            ->url(app(LocalizedUrlGenerator::class)->tag($tag, $locale))
            ->toScript();
    }
}
