<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Resources\TagResource;
use App\Models\Tag;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTag extends EditRecord
{
    protected static string $resource = TagResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Tag $record */
        $record = $this->getRecord();

        return TagResource::fillFormData($record);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        ['attributes' => $attributes, 'translations' => $translations] = TagResource::mutateFormData($data);

        /** @var Tag $record */
        $record->fill($attributes);
        $record->save();

        TagResource::persistTranslations($record, $translations);

        return $record->fresh(['translations']);
    }
}
