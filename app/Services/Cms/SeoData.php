<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;

class SeoData
{
    public function __construct(private readonly LocalizedUrlGenerator $urls)
    {
    }

    public function forPage(Page $page, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return $this->build($page->seoTitleForLocale($locale), $page->seoDescriptionForLocale($locale), $this->urls->page($page, $locale));
    }

    public function forArticle(Article $article, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return $this->build($article->seoTitleForLocale($locale), $article->seoDescriptionForLocale($locale), $this->urls->article($article, $locale));
    }

    public function forCategory(Category $category, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return $this->build($category->seoTitleForLocale($locale), $category->seoDescriptionForLocale($locale), $this->urls->category($category, $locale));
    }

    public function forTag(Tag $tag, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return $this->build($tag->seoTitleForLocale($locale), $tag->seoDescriptionForLocale($locale), $this->urls->tag($tag, $locale));
    }

    public function forCurrentRoute(): array
    {
        return [
            'locale' => app()->getLocale(),
            'canonical' => $this->urls->canonicalForCurrentRoute(),
            'alternates' => $this->urls->alternatesForCurrentRoute(),
        ];
    }

    private function build(?string $title, ?string $description, string $canonical): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'alternates' => $this->urls->alternatesForCurrentRoute(),
        ];
    }
}
