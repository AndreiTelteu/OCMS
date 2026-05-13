<?php

namespace App\Services\Cms;

use App\Models\LocalizedRoute;
use App\Models\Page;

class RootContentResolver
{
    public function __construct(private readonly LocalizedRouteRegistry $routes) {}

    public function resolve(string $locale, ?string $path = null): ?LocalizedRoute
    {
        $path = trim((string) $path, '/');

        if ($path === '') {
            return $this->routes->find($locale, '')
                ?? $this->routes->find(config('cms.fallback_locale'), '')
                ?? $this->resolveHomeByFlag($locale);
        }

        return $this->routes->find($locale, $path)
            ?? $this->routes->find(config('cms.fallback_locale'), $path);
    }

    private function resolveHomeByFlag(string $locale): ?LocalizedRoute
    {
        $home = Page::query()
            ->where('is_home', true)
            ->where('status', 'published')
            ->where(function ($query): void {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->first();

        if (! $home) {
            return null;
        }

        return $this->routes->find($locale, $home->rootPathForLocale($locale))
            ?? $this->routes->find(config('cms.fallback_locale'), $home->rootPathForLocale(config('cms.fallback_locale')));
    }
}
