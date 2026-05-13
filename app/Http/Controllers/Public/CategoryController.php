<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Cms\SchemaBuilder;
use App\Services\Cms\SeoData;

class CategoryController extends Controller
{
    public function __construct(private readonly SeoData $seo, private readonly SchemaBuilder $schema)
    {
    }

    public function __invoke(string $path)
    {
        $category = Category::query()->whereTranslation('path', trim($path, '/'))->firstOrFail();

        return view('cms.content', [
            'model' => $category,
            'seo' => $this->seo->forCategory($category),
            'schema' => $this->schema->forCategory($category),
        ]);
    }
}
