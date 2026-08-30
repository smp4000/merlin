<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lokaler Bootstrap-Administrator
    |--------------------------------------------------------------------------
    |
    | Diese Werte dienen ausschließlich dazu, in lokalen Entwicklungs- und
    | Testumgebungen den ersten Zugang zum Filament-Panel anzulegen. Das spätere
    | produktive Super-Admin- und Rollenmodell darf sich nicht auf diese
    | Bootstrap-Konfiguration verlassen.
    |
    */
    'bootstrap_admin' => [
        'name' => env('MERLIN_ADMIN_NAME', 'Merlin Administrator'),
        'email' => env('MERLIN_ADMIN_EMAIL'),
        'password' => env('MERLIN_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Öffentliche Partnerregistrierung
    |--------------------------------------------------------------------------
    |
    | Dokumentversionen und Hashwerte sind technische Nachweise. Die lokalen
    | Entwicklungswerte müssen vor Go-live durch juristisch freigegebene,
    | unveränderlich veröffentlichte Dokumentversionen ersetzt werden.
    |
    */
    'registration' => [
        'token_lifetime_minutes' => 60,
        'pending_retention_days' => 7,
        'supported_countries' => ['DE', 'AT', 'CH'],
        'supported_locales' => ['de', 'en'],
        'country_timezones' => [
            'DE' => 'Europe/Berlin',
            'AT' => 'Europe/Vienna',
            'CH' => 'Europe/Zurich',
        ],
        'documents' => [
            'terms' => [
                'version' => env('MERLIN_TERMS_VERSION', 'development-1'),
                'locale' => 'de',
                'path' => resource_path('legal/terms-development-1.md'),
            ],
            'privacy' => [
                'version' => env('MERLIN_PRIVACY_VERSION', 'development-1'),
                'locale' => 'de',
                'path' => resource_path('legal/privacy-development-1.md'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Öffentliches Bankverzeichnis
    |--------------------------------------------------------------------------
    |
    | Die URL dient nur als sicherer Startwert. Berechtigte Plattformrollen
    | können spätere Bundesbank-Versionen auditiert in der Datenbank hinterlegen.
    |
    */
    'bank_directory' => [
        'source_url' => env(
            'BANK_DIRECTORY_SOURCE_URL',
            'https://www.bundesbank.de/resource/blob/926192/b27b518a016ea7ca7af321eb7289fcf4/472B63F073F071307366337C94F8C870/blz-aktuell-csv-data.csv',
        ),
    ],
];
