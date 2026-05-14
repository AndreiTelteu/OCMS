<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class RootRouteCollisionException extends RuntimeException
{
    public static function forPath(Model $routable, string $locale, string $path, Model $conflictingRoutable): self
    {
        $normalizedPath = trim($path, '/');
        $pathLabel = $normalizedPath === '' ? '/' : "/{$normalizedPath}";

        return new self(sprintf(
            'Cannot register %s for [%s] in locale [%s] because it is already claimed by %s.',
            $routable::class,
            $pathLabel,
            $locale,
            $conflictingRoutable::class,
        ));
    }
}
