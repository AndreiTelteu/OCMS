<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Category $record */
        $record = $this->getRecord();

        return CategoryResource::fillFormData($record);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        ['attributes' => $attributes, 'translations' => $translations] = CategoryResource::mutateFormData($data);

        /** @var Category $record */
        $record->fill($attributes);
        $record->save();

        CategoryResource::persistTranslations($record, $translations);

        return $record->fresh(['translations', 'parent.translations']);
    }
}
