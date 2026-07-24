<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\ArticleTranslation;
use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Observers\ArticleObserver;
use App\Observers\ArticleTranslationObserver;
use App\Observers\CategoryObserver;
use App\Observers\CategoryTranslationObserver;
use App\Observers\PageObserver;
use App\Observers\PageTranslationObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define(
            'useFilamentMcp',
            fn ($user): bool => filled(env('FILAMENT_MCP_ALLOWED_EMAIL'))
                && $user->email === env('FILAMENT_MCP_ALLOWED_EMAIL'),
        );

        Page::observe(PageObserver::class);
        PageTranslation::observe(PageTranslationObserver::class);
        Article::observe(ArticleObserver::class);
        ArticleTranslation::observe(ArticleTranslationObserver::class);
        Category::observe(CategoryObserver::class);
        CategoryTranslation::observe(CategoryTranslationObserver::class);
    }
}
