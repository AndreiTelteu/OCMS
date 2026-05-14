<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Article $record */
        $record = $this->getRecord();

        return ArticleResource::fillFormData($record);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        [
            'attributes' => $attributes,
            'category_ids' => $categoryIds,
            'tag_ids' => $tagIds,
            'translations' => $translations,
        ] = ArticleResource::mutateFormData($data);

        /** @var Article $record */
        $record->fill($attributes);
        $record->save();

        ArticleResource::persistTranslations($record, $translations);
        ArticleResource::syncRelationships($record, $categoryIds, $tagIds);

        return $record->fresh(['translations', 'author', 'categories', 'tags']);
    }
}
