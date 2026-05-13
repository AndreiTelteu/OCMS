<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\Cms\LocalizedRouteRegistry;

class ArticleObserver
{
    public function __construct(private readonly LocalizedRouteRegistry $routes)
    {
    }

    public function saved(Article $article): void
    {
        if (! $article->isPublished()) {
            $this->routes->forget($article);
            return;
        }

        $this->routes->syncModel($article, function (string $locale) use ($article): ?string {
            return $article->slugForLocale($locale);
        }, 'content.root');
    }

    public function deleted(Article $article): void
    {
        $this->routes->forget($article);
    }
}
