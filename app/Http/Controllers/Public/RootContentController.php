<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
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
    ) {
    }

    public function __invoke(Request $request)
    {
        $path = $request->route('path');
        $route = $this->resolver->resolve(app()->getLocale(), $path);

        if (! $route || ! $route->routable) {
            abort(404);
        }

        $model = $route->routable;

        return view('cms.content', [
            'model' => $model,
            'seo' => $this->seo->forCurrentRoute(),
            'schema' => method_exists($this->schema, 'forPage') && $model instanceof \App\Models\Page ? $this->schema->forPage($model) : null,
        ]);
    }
}
