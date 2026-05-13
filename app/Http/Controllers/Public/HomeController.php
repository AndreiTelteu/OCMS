<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;

class HomeController extends Controller
{
    public function __invoke()
    {
        $page = Page::query()->where('is_home', true)->where('status', 'published')->first();

        return view('cms.home', [
            'page' => $page,
        ]);
    }
}
