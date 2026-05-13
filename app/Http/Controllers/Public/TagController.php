<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Services\Cms\SchemaBuilder;
use App\Services\Cms\SeoData;

class TagController extends Controller
{
    public function __construct(private readonly SeoData $seo, private readonly SchemaBuilder $schema)
    {
    }

    public function __invoke(string $slug)
    {
        $tag = Tag::query()->whereTranslation('slug', trim($slug, '/'))->firstOrFail();

        return view('cms.content', [
            'model' => $tag,
            'seo' => $this->seo->forTag($tag),
            'schema' => $this->schema->forTag($tag),
        ]);
    }
}
