<?php

use App\Http\Middleware\CanonicalizeCmsLocale;
use App\Http\Middleware\ResolveCmsLocale;
use App\Http\Middleware\ShareSeoContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use NielsNumbers\LaravelLocalizer\Middleware\RedirectLocale;
use NielsNumbers\LaravelLocalizer\Middleware\SetLocale;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(remove: [
            SubstituteBindings::class,
        ]);

        $middleware->web(append: [
            ResolveCmsLocale::class,
            SetLocale::class,
            RedirectLocale::class,
            CanonicalizeCmsLocale::class,
            SubstituteBindings::class,
            ShareSeoContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
