<?php

namespace App\Services\Cms;

use App\Models\LocalizedRoute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LocalizedRouteRegistry
{
    public function sync(Model $routable, string $locale, string $path, ?string $routeName = null): LocalizedRoute
    {
        return LocalizedRoute::updateOrCreate(
            [
                'locale' => $locale,
                'path' => trim($path, '/'),
            ],
            [
                'route_name' => $routeName,
                'routable_type' => $routable::class,
                'routable_id' => $routable->getKey(),
            ]
        );
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
