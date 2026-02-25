<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Locale Namespace For Mobile
    |--------------------------------------------------------------------------
    |
    | Flutter can request this namespace from /api/v1/localization/translations
    | and cache it locally for realtime language switching.
    |
    */
    'default_namespace' => 'mobile',

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | Keep this list in sync with Flutter locale selector options.
    |
    */
    'supported_locales' => [
        'en' => [
            'name' => 'English',
            'native_name' => 'English',
            'rtl' => false,
        ],
        'so' => [
            'name' => 'Somali',
            'native_name' => 'Soomaali',
            'rtl' => false,
        ],
        'ar' => [
            'name' => 'Arabic',
            'native_name' => 'Arabic',
            'rtl' => true,
        ],
    ],
];

