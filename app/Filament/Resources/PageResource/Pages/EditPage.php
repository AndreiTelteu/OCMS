<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use App\Models\Page;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Page $record */
        $record = $this->getRecord();

        return PageResource::fillFormData($record);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        ['attributes' => $attributes, 'translations' => $translations] = PageResource::mutateFormData($data);

        /** @var Page $record */
        $record->fill($attributes);
        $record->save();

        PageResource::persistTranslations($record, $translations);

        return $record->fresh(['translations']);
    }
}
