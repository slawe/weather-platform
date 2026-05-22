<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Podrazumevani provider za vremenske podatke
    |--------------------------------------------------------------------------
    |
    | Ova vrednost određuje koji source provider koristimo za dohvat podataka.
    | Trenutno imamo samo Open-Meteo, ali je struktura ostavljena tako da
    | kasnije možemo lako dodati drugi provider bez menjanja application sloja.
    |
    */

    'default_provider' => env('WEATHER_DEFAULT_PROVIDER', 'open-meteo'),

    /*
    |--------------------------------------------------------------------------
    | Lokacije koje pratimo
    |--------------------------------------------------------------------------
    |
    | Za početak možemo držati lokacije u bazi, ali i konfiguracija može biti
    | korisna za podrazumevane vrednosti ili seed podatke.
    |
    */

    'default_locations' => [
        [
            'id' => '11111111-1111-1111-1111-111111111111',
            'name' => 'Belgrade',
            'country' => 'Serbia',
            'latitude' => 44.8178,
            'longitude' => 20.4569,
        ],
        [
            'id' => '22222222-2222-2222-2222-222222222222',
            'name' => 'Novi Sad',
            'country' => 'Serbia',
            'latitude' => 45.2671,
            'longitude' => 19.8335,
        ],
        [
            'id' => '33333333-3333-3333-3333-333333333333',
            'name' => 'Niš',
            'country' => 'Serbia',
            'latitude' => 43.3209,
            'longitude' => 21.8958,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Open-Meteo podešavanja
    |--------------------------------------------------------------------------
    |
    | Ovde držimo podešavanja vezana za konkretan provider.
    | Kasnije možemo dodati nove sekcije za druge source-ove.
    |
    */

    'providers' => [
        'open-meteo' => [
            'base_url' => env('OPEN_METEO_BASE_URL', 'https://api.open-meteo.com/v1'),
            'timezone' => env('OPEN_METEO_TIMEZONE', 'auto'),
            'current' => [
                'temperature_2m',
                'wind_speed_10m',
                'weather_code',
            ],
        ],
    ],
];
