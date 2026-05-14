<?php

namespace App\Observers;

use App\Models\ArticleTranslation;
use App\Services\Cms\LocalizedRouteRegistry;

class ArticleTranslationObserver
{
    public function __construct(private readonly LocalizedRouteRegistry $routes)
    {
    }

    public function saved(ArticleTranslation $translation): void
    {
        $article = $translation->article;

        if (! $article) {
            return;
        }

        if (! $article->isPublished()) {
            $this->routes->forget($article);

            return;
        }

        $this->routes->syncModel($article, fn (string $locale): ?string => $article->slugForLocale($locale), 'content.root');
    }

    public function deleted(ArticleTranslation $translation): void
    {
        $article = $translation->article;

        if (! $article) {
            return;
        }

        if (! $article->isPublished()) {
            $this->routes->forget($article);

            return;
        }

        $this->routes->syncModel($article, fn (string $locale): ?string => $article->slugForLocale($locale), 'content.root');
    }
}
