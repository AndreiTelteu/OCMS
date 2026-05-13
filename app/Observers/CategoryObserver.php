<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Cms\CategoryPathSynchronizer;

class CategoryObserver
{
    public function __construct(private readonly CategoryPathSynchronizer $paths)
    {
    }

    public function saved(Category $category): void
    {
        $this->paths->sync($category);
    }
}
