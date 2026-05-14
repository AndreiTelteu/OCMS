<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;

class SitemapBuilder
{
    public function __construct(private readonly LocalizedUrlGenerator $urls)
    {
    }

    public function xml(): string
    {
        $entries = [];
        $canonicalLocale = config('cms.fallback_locale');

        foreach (Page::query()->get()->filter(fn (Page $page): bool => $page->isPublished()) as $page) {
            $entries[] = $this->entry(
                $this->urls->page($page, $canonicalLocale),
                $this->urls->alternatesForPage($page),
            );
        }

        foreach (Article::query()->get()->filter(fn (Article $article): bool => $article->isPublished()) as $article) {
            $entries[] = $this->entry(
                $this->urls->article($article, $canonicalLocale),
                $this->urls->alternatesForArticle($article),
            );
        }

        foreach (Category::query()->get()->filter(fn (Category $category): bool => $category->isPublished()) as $category) {
            $entries[] = $this->entry(
                $this->urls->category($category, $canonicalLocale),
                $this->urls->alternatesForCategory($category),
            );
        }

        foreach (Tag::query()->get()->filter(fn (Tag $tag): bool => $tag->isPublished()) as $tag) {
            $entries[] = $this->entry(
                $this->urls->tag($tag, $canonicalLocale),
                $this->urls->alternatesForTag($tag),
            );
        }

        $body = collect($entries)->map(function (array $entry) {
            $alternates = collect($entry['alternates'])
                ->map(fn (string $url, string $locale): string => '    <xhtml:link rel="alternate" hreflang="'.e($locale).'" href="'.e($url).'" />')
                ->implode("\n");

            return "  <url>\n    <loc>".e($entry['loc'])."</loc>\n{$alternates}\n  </url>";
        })->implode("
");

        return '<?xml version="1.0" encoding="UTF-8"?>'."
".
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'."
".
            $body."
".
            '</urlset>';
    }

    private function entry(string $loc, array $alternates): array
    {
        return [
            'loc' => $loc,
            'alternates' => $alternates,
        ];
    }
}
