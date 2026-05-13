<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\Cms\LocalizedRouteRegistry;

class PageObserver
{
    public function __construct(private readonly LocalizedRouteRegistry $routes) {}

    public function saved(Page $page): void
    {
        if (! $page->isPublished()) {
            $this->routes->forget($page);

            return;
        }

        $this->routes->syncModel($page, function (string $locale) use ($page): ?string {
            return $page->rootPathForLocale($locale);
        }, 'content.root');
    }

    public function deleted(Page $page): void
    {
        $this->routes->forget($page);
    }
}
