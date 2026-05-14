<?php

namespace App\Observers;

use App\Models\CategoryTranslation;
use App\Services\Cms\CategoryPathSynchronizer;

class CategoryTranslationObserver
{
    public function __construct(private readonly CategoryPathSynchronizer $paths)
    {
    }

    public function saved(CategoryTranslation $translation): void
    {
        $category = $translation->category;

        if (! $category) {
            return;
        }

        $this->paths->sync($category);
    }
}
