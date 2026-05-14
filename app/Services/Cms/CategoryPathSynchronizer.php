<?php

namespace App\Services\Cms;

use App\Models\Category;
use App\Models\CategoryTranslation;

class CategoryPathSynchronizer
{
    public function sync(Category $category): void
    {
        /** @var Category|null $category */
        $category = $category->fresh(['translations', 'parent.translations']);

        if (! $category) {
            return;
        }

        $this->syncCategory($category);

        $children = $category->children()
            ->with(['translations', 'parent.translations'])
            ->get();

        foreach ($children as $child) {
            $this->sync($child);
        }
    }

    public function pathFor(Category $category, string $locale): ?string
    {
        $slug = trim((string) ($category->slugForLocale($locale) ?? ''), '/');

        if ($slug === '') {
            return null;
        }

        $parentPath = trim((string) ($category->parent?->pathForLocale($locale) ?? ''), '/');

        return trim(collect([$parentPath, $slug])->filter()->implode('/'), '/');
    }

    private function syncCategory(Category $category): void
    {
        $category->translations
            ->each(function (CategoryTranslation $translation) use ($category): void {
                $path = $this->pathFor($category, $translation->locale);

                if ($path === null || $translation->path === $path) {
                    return;
                }

                $translation->forceFill([
                    'path' => $path,
                ])->saveQuietly();
            });
    }
}
