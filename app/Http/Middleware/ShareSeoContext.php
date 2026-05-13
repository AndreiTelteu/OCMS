<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use App\Services\Cms\LocalizedUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

class ShareSeoContext
{
    public function __construct(private readonly LocalizedUrlGenerator $urls)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        View::share('seo', [
            'locale' => app()->getLocale(),
            'canonical' => $this->urls->canonicalForCurrentRoute(),
            'alternates' => $this->urls->alternatesForCurrentRoute(),
            'switcher' => $this->urls->switcherForCurrentRoute(),
        ]);

        return $next($request);
    }
}
