<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use NielsNumbers\LaravelLocalizer\Facades\Localizer;
use Symfony\Component\HttpFoundation\Response;

class CanonicalizeCmsLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = app()->getLocale();
        $default = config('app.fallback_locale');

        if (! config('localizer.redirect_enabled', true)) {
            return $next($request);
        }

        if (Localizer::hideDefaultLocale() && $locale === $default && $request->segment(1) === $default) {
            $target = '/'.ltrim($request->path(), '/');
            $target = preg_replace('#^'.preg_quote($default, '#').'/#', '', $target) ?: '/';

            return redirect($target, 302);
        }

        return $next($request);
    }
}
