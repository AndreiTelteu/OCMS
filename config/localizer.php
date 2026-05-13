<?php

use NielsNumbers\LaravelLocalizer\Detectors\BrowserDetector;
use NielsNumbers\LaravelLocalizer\Detectors\UserDetector;

return [
    'supported_locales' => config('cms.supported_locales'),
    'hide_default_locale' => ! config('cms.main_lang_prefix'),
    'redirect_enabled' => true,
    'persist_locale' => [
        'session' => true,
        'cookie' => true,
    ],
    'detectors' => [
        UserDetector::class,
        BrowserDetector::class,
    ],
    'locale_directions' => [
        'en' => 'ltr',
        'ro' => 'ltr',
    ],
];
