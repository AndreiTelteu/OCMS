<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Cms\SchemaBuilder;
use App\Services\Cms\SeoData;

class HomeController extends Controller
{
    public function __construct(
        private readonly SeoData $seo,
        private readonly SchemaBuilder $schema,
    ) {}

    public function __invoke()
    {
        $page = Page::query()
            ->where('is_home', true)
            ->where('status', 'published')
            ->where(function ($query): void {
                $query
                    ->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->first();

        if ($page) {
            return view('cms.content', [
                'model' => $page,
                'seo' => $this->seo->forPage($page),
                'schema' => $this->schema->forPage($page),
            ]);
        }

        return view('cms.home', [
            'page' => $page,
            'seo' => $this->seo->forCurrentRoute(),
            'schema' => null,
        ]);
    }
}
