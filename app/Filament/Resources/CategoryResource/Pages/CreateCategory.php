<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        ['attributes' => $attributes, 'translations' => $translations] = CategoryResource::mutateFormData($data);

        /** @var Category $category */
        $category = new Category;
        $category->fill($attributes);
        $category->save();

        CategoryResource::persistTranslations($category, $translations);

        return $category->fresh(['translations', 'parent.translations']);
    }
}
