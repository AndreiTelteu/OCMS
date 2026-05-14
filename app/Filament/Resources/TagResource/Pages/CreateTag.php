<?php

namespace App\Filament\Resources\TagResource\Pages;

use App\Filament\Resources\TagResource;
use App\Models\Tag;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTag extends CreateRecord
{
    protected static string $resource = TagResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        ['attributes' => $attributes, 'translations' => $translations] = TagResource::mutateFormData($data);

        /** @var Tag $tag */
        $tag = new Tag;
        $tag->fill($attributes);
        $tag->save();

        TagResource::persistTranslations($tag, $translations);

        return $tag->fresh(['translations']);
    }
}
