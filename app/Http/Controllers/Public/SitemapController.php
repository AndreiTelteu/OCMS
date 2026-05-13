<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Cms\SitemapBuilder;

class SitemapController extends Controller
{
    public function __construct(private readonly SitemapBuilder $sitemap)
    {
    }

    public function __invoke()
    {
        return response($this->sitemap->xml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }
}
