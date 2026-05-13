<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Observers\ArticleObserver;
use App\Observers\CategoryObserver;
use App\Observers\PageObserver;
use App\Observers\PageTranslationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Page::observe(PageObserver::class);
        PageTranslation::observe(PageTranslationObserver::class);
        Article::observe(ArticleObserver::class);
        Category::observe(CategoryObserver::class);
    }
}
