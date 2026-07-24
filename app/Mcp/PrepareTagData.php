<?php

namespace App\Mcp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Mattiasgeniar\FilamentMcp\Contracts\PreparesRecordData;

class PrepareTagData implements PreparesRecordData
{
    public function __invoke(array $data, ?Model $record): array
    {
        $attributes = Arr::only($data, ['status']);

        $translations = collect(config('cms.supported_locales'))
            ->mapWithKeys(function (string $locale) use ($data): array {
                $translation = Arr::only((array) data_get($data, "translations.{$locale}", []), [
                    'name',
                    'slug',
                    'description',
                    'seo_title',
                    'seo_description',
                ]);

                if (array_key_exists('slug', $translation)) {
                    $translation['slug'] = filled($translation['slug'])
                        ? trim((string) $translation['slug'], '/')
                        : null;
                }

                return [$locale => $translation];
            })
            ->filter(fn (array $translation): bool => $translation !== [])
            ->all();

        foreach ($translations as $locale => $translation) {
            $attributes[$locale] = $translation;
        }

        return $attributes;
    }
}
