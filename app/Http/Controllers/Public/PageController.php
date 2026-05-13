<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Cms\SchemaBuilder;
use App\Services\Cms\SeoData;

class PageController extends Controller
{
    public function __construct(private readonly SeoData $seo, private readonly SchemaBuilder $schema)
    {
    }

    public function __invoke(Page $page)
    {
        return view('cms.content', [
            'model' => $page,
            'seo' => $this->seo->forPage($page),
            'schema' => $this->schema->forPage($page),
        ]);
    }
}
