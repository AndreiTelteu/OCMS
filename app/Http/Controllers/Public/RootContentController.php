<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Page;
use App\Services\Cms\RootContentResolver;
use App\Services\Cms\SchemaBuilder;
use App\Services\Cms\SeoData;
use Illuminate\Http\Request;

class RootContentController extends Controller
{
    public function __construct(
        private readonly RootContentResolver $resolver,
        private readonly SeoData $seo,
        private readonly SchemaBuilder $schema,
    ) {}

    public function __invoke(Request $request)
    {
        $path = $request->route('path');
        $route = $this->resolver->resolve(app()->getLocale(), $path);

        if (! $route || ! $route->routable) {
            abort(404);
        }

        $model = $route->routable;
        $seo = match (true) {
            $model instanceof Page => $this->seo->forPage($model),
            $model instanceof Article => $this->seo->forArticle($model),
            default => $this->seo->forCurrentRoute(),
        };

        $schema = match (true) {
            $model instanceof Page => $this->schema->forPage($model),
            $model instanceof Article => $this->schema->forArticle($model),
            default => null,
        };

        return view('cms.content', [
            'model' => $model,
            'seo' => $seo,
            'schema' => $schema,
        ]);
    }
}
