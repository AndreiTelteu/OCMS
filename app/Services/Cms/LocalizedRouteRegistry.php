<?php

namespace App\Services\Cms;

use App\Exceptions\RootRouteCollisionException;
use App\Models\LocalizedRoute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LocalizedRouteRegistry
{
    public function sync(Model $routable, string $locale, string $path, ?string $routeName = null): LocalizedRoute
    {
        $normalizedPath = trim($path, '/');

        $route = LocalizedRoute::query()
            ->where('locale', $locale)
            ->where('path', $normalizedPath)
            ->first();

        if (
            $route !== null
            && ($route->routable_type !== $routable::class || (int) $route->routable_id !== (int) $routable->getKey())
        ) {
            $conflictingRoutable = $route->routable;

            if ($conflictingRoutable instanceof Model) {
                throw RootRouteCollisionException::forPath($routable, $locale, $normalizedPath, $conflictingRoutable);
            }

            throw new RootRouteCollisionException(sprintf(
                'Cannot register %s for [%s] in locale [%s] because the path is already claimed.',
                $routable::class,
                $normalizedPath === '' ? '/' : "/{$normalizedPath}",
                $locale,
            ));
        }

        $route ??= new LocalizedRoute([
            'locale' => $locale,
            'path' => $normalizedPath,
        ]);

        $route->fill([
            'route_name' => $routeName,
            'routable_type' => $routable::class,
            'routable_id' => $routable->getKey(),
        ]);
        $route->save();

        return $route;
    }

    public function syncModel(Model $routable, callable $pathResolver, ?string $routeName = null): void
    {
        DB::transaction(function () use ($pathResolver, $routeName, $routable): void {
            $this->forget($routable);

            foreach (config('cms.supported_locales') as $locale) {
                $path = $pathResolver($locale);

                if ($path !== null) {
                    $this->sync($routable, $locale, $path, $routeName);
                }
            }
        });
    }

    public function forget(Model $routable): void
    {
        LocalizedRoute::query()
            ->where('routable_type', $routable::class)
            ->where('routable_id', $routable->getKey())
            ->delete();
    }

    public function find(string $locale, string $path): ?LocalizedRoute
    {
        return LocalizedRoute::query()
            ->where('locale', $locale)
            ->where('path', trim($path, '/'))
            ->first();
    }
}
