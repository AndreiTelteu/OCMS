<?php

namespace App\Services\Cms;

use App\Models\Category;

class CategoryPathSynchronizer
{
    public function sync(Category $category): void
    {
        foreach (config('cms.supported_locales') as $locale) {
            $translation = $category->translateOrNew($locale);
            $slug = $translation->slug ?: $category->slugForLocale($locale);
            $parentPath = $category->parent?->pathForLocale($locale);
            $translation->path = trim(($parentPath ? $parentPath.'/' : '').$slug, '/');
            $translation->save();
        }
    }
}
