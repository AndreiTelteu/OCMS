<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\Cms\SchemaBuilder;
use App\Services\Cms\SeoData;

class ArticleController extends Controller
{
    public function __construct(private readonly SeoData $seo, private readonly SchemaBuilder $schema)
    {
    }

    public function __invoke(Article $article)
    {
        return view('cms.content', [
            'model' => $article,
            'seo' => $this->seo->forArticle($article),
            'schema' => $this->schema->forArticle($article),
        ]);
    }
}
