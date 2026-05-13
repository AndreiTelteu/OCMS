<?php

namespace App\Filament\Resources\PageResource\Pages;

use App\Filament\Resources\PageResource;
use App\Models\Page;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        ['attributes' => $attributes, 'translations' => $translations] = PageResource::mutateFormData($data);

        /** @var Page $page */
        $page = new Page;
        $page->fill($attributes);
        $page->save();

        PageResource::persistTranslations($page, $translations);

        return $page->fresh(['translations']);
    }
}
