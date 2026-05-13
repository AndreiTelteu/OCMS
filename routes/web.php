<?php

use App\Http\Controllers\Public\CategoryController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\RootContentController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\TagController;
use Illuminate\Support\Facades\Route;
use NielsNumbers\LaravelLocalizer\Facades\Localizer;

Route::localize(function (): void {
    Route::get('/', HomeController::class)->name('home');
    Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
});

Route::translate(function (): void {
    Route::get(Localizer::url('category/{path}'), CategoryController::class)->name('category.show');
    Route::get(Localizer::url('tag/{slug}'), TagController::class)->name('tag.show');
});

Route::localize(function (): void {
    Route::get('/{path?}', RootContentController::class)
        ->where('path', '.*')
        ->name('content.root');
});
