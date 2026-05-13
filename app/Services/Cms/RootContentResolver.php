<?php

namespace App\Services\Cms;

use App\Models\LocalizedRoute;
use App\Models\Page;

class RootContentResolver
{
    public function __construct(private readonly LocalizedRouteRegistry $routes)
    {
    }

    public function resolve(string $locale, ?string $path = null): ?LocalizedRoute
    {
        $path = trim((string) $path, '/');

        if ($path === '') {
            $home = Page::query()->where('is_home', true)->where('status', 'published')->first();

            if ($home) {
                return $this->routes->find($locale, (string) $home->slugForLocale($locale));
            }

            return null;
        }

        return $this->routes->find($locale, $path)
            ?? $this->routes->find(config('cms.fallback_locale'), $path);
    }
}
