<?php

return [

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ konekcija
    |--------------------------------------------------------------------------
    |
    | Osnovna konekcija ka RabbitMQ brokeru.
    |
    */

    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'demo'),
    'password' => env('RABBITMQ_PASSWORD', 'demo'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    /*
    |--------------------------------------------------------------------------
    | Exchange podešavanja
    |--------------------------------------------------------------------------
    |
    | Producer šalje poruke na topic exchange.
    |
    */

    'exchange' => env('RABBITMQ_EXCHANGE', 'weather.events'),
    'exchange_type' => env('RABBITMQ_EXCHANGE_TYPE', 'topic'),

    /*
    |--------------------------------------------------------------------------
    | Routing keys
    |--------------------------------------------------------------------------
    |
    | Ovaj niz služi da centralizujemo routing key nazive za evente.
    |
    */

    'routing_keys' => [
        'weather_snapshot_fetched' => 'weather.snapshot.fetched',
    ],
];
