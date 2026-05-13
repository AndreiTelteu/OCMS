<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasTranslationFallbacks
{
    protected function translatedValue(string $attribute, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $translation = $this->translate($locale, false) ?: $this->translate(config('cms.fallback_locale'), false);

        return $translation?->{$attribute};
    }

    protected function firstTranslatedModel(?string $locale = null): ?Model
    {
        $locale ??= app()->getLocale();

        return $this->translate($locale, false) ?: $this->translate(config('cms.fallback_locale'), false);
    }
}
