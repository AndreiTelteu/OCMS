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

        foreach (Page::query()->where('status', 'published')->get() as $page) {
            foreach (config('cms.supported_locales') as $locale) {
                $entries[] = $this->entry(
                    $this->urls->page($page, $locale),
                    $this->urls->alternatesForPage($page),
                );
            }
        }

        foreach (Article::query()->where('status', 'published')->get() as $article) {
            foreach (config('cms.supported_locales') as $locale) {
                $entries[] = $this->entry(
                    $this->urls->article($article, $locale),
                    $this->urls->alternatesForArticle($article),
                );
            }
        }

        foreach (Category::query()->where('status', 'published')->get() as $category) {
            foreach (config('cms.supported_locales') as $locale) {
                $entries[] = $this->entry(
                    $this->urls->category($category, $locale),
                    $this->urls->alternatesForCategory($category),
                );
            }
        }

        foreach (Tag::query()->where('status', 'published')->get() as $tag) {
            foreach (config('cms.supported_locales') as $locale) {
                $entries[] = $this->entry(
                    $this->urls->tag($tag, $locale),
                    $this->urls->alternatesForTag($tag),
                );
            }
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
