<?php

namespace App\Observers;

use App\Models\PageTranslation;
use App\Services\Cms\LocalizedRouteRegistry;

class PageTranslationObserver
{
    public function __construct(private readonly LocalizedRouteRegistry $routes) {}

    public function saved(PageTranslation $translation): void
    {
        $page = $translation->page;

        if (! $page) {
            return;
        }

        if (! $page->isPublished()) {
            $this->routes->forget($page);

            return;
        }

        $this->routes->syncModel($page, fn (string $locale): ?string => $page->rootPathForLocale($locale), 'content.root');
    }

    public function deleted(PageTranslation $translation): void
    {
        $page = $translation->page;

        if (! $page) {
            return;
        }

        if (! $page->isPublished()) {
            $this->routes->forget($page);

            return;
        }

        $this->routes->syncModel($page, fn (string $locale): ?string => $page->rootPathForLocale($locale), 'content.root');
    }
}
