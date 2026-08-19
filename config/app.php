<?php

// Solo las claves que sobrescribimos. Laravel completa el resto con sus
// valores por defecto, asi que este archivo puede ser corto a proposito.

return [
    'name'   => env('APP_NAME', 'holaprivet'),
    'env'    => env('APP_ENV', 'production'),
    'debug'  => (bool) env('APP_DEBUG', false),
    'url'    => env('APP_URL', 'https://holaprivet.com'),
    'key'    => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    // El motivo de este archivo: sin el, la zona horaria se queda en UTC.
    'timezone' => env('APP_TIMEZONE', 'Europe/Madrid'),

    'locale'          => env('APP_LOCALE', 'ru'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'es'),
    'faker_locale'    => 'es_ES',

    'previous_keys' => array_filter(
        explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
    ),
];
