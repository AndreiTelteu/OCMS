<?php

return [
    'supported_locales' => ['en', 'ro'],
    'default_locale' => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'main_lang_prefix' => (bool) env('APP_MAIN_LANG_PREFIX', false),
    'reserved_root_slugs' => [
        'admin',
        'livewire',
        'up',
    ],
];
