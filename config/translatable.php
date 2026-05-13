<?php

use Astrotomic\Translatable\Validation\RuleFactory;

return [
    'locales' => config('cms.supported_locales'),
    'locale_separator' => '-',
    'locale' => null,
    'use_fallback' => true,
    'use_property_fallback' => true,
    'fallback_locale' => config('cms.fallback_locale'),
    'translation_model_namespace' => null,
    'translation_suffix' => 'Translation',
    'locale_key' => 'locale',
    'to_array_always_loads_translations' => true,
    'rule_factory' => [
        'format' => RuleFactory::FORMAT_ARRAY,
        'prefix' => '%',
        'suffix' => '%',
    ],
    'translations_wrapper' => 'translations',
];
