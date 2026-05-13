<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Support\Str;

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
                $entries[] = $this->entry($this->urls->page($page, $locale), $locale);
            }
        }

        foreach (Article::query()->where('status', 'published')->get() as $article) {
            foreach (config('cms.supported_locales') as $locale) {
                $entries[] = $this->entry($this->urls->article($article, $locale), $locale);
            }
        }

        foreach (Category::query()->where('status', 'published')->get() as $category) {
            foreach (config('cms.supported_locales') as $locale) {
                $entries[] = $this->entry($this->urls->category($category, $locale), $locale);
            }
        }

        foreach (Tag::query()->where('status', 'published')->get() as $tag) {
            foreach (config('cms.supported_locales') as $locale) {
                $entries[] = $this->entry($this->urls->tag($tag, $locale), $locale);
            }
        }

        $body = collect($entries)->map(function (array $entry) {
            return '  <url><loc>'.e($entry['loc']).'</loc></url>';
        })->implode("
");

        return '<?xml version="1.0" encoding="UTF-8"?>'."
".
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."
".
            $body."
".
            '</urlset>';
    }

    private function entry(string $loc, string $locale): array
    {
        return compact('loc', 'locale');
    }
}
