<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NielsNumbers\LaravelLocalizer\Facades\Localizer;
use Symfony\Component\HttpFoundation\Response;

class ResolveCmsLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $pathLocale = $request->segment(1);
        $locale = Localizer::canonicalize($pathLocale);

        if (! Localizer::isSupported($locale)) {
            $locale = config('app.fallback_locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
