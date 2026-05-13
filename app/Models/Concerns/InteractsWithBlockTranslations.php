<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Collection;

trait InteractsWithBlockTranslations
{
    public function translatedFields(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $fallback = config('cms.fallback_locale');

        return $this->translationValues
            ->whereIn('locale', [$locale, $fallback])
            ->sortBy(fn ($value) => $value->locale === $locale ? 0 : 1)
            ->unique('field_key')
            ->mapWithKeys(fn ($value) => [$value->field_key => $value->value])
            ->all();
    }

    public function translatedItems(?string $locale = null): Collection
    {
        return $this->items->map(function ($item) use ($locale) {
            $item->setAttribute('translated_fields', $item->translatedFields($locale));

            return $item;
        });
    }
}
