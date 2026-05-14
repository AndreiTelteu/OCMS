<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        [
            'attributes' => $attributes,
            'category_ids' => $categoryIds,
            'tag_ids' => $tagIds,
            'translations' => $translations,
        ] = ArticleResource::mutateFormData($data);

        /** @var Article $article */
        $article = new Article;
        $article->fill($attributes);
        $article->save();

        ArticleResource::persistTranslations($article, $translations);
        ArticleResource::syncRelationships($article, $categoryIds, $tagIds);

        return $article->fresh(['translations', 'author', 'categories', 'tags']);
    }
}
