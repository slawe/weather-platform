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
    | Processing servis sluša evente sa glavnog exchange-a.
    |
    */

    'exchange' => env('RABBITMQ_EXCHANGE', 'weather.events'),
    'exchange_type' => env('RABBITMQ_EXCHANGE_TYPE', 'topic'),

    /*
    |--------------------------------------------------------------------------
    | Queue topologija
    |--------------------------------------------------------------------------
    |
    | Glavna queue, retry queue i dead-letter queue za processing servis.
    |
    */

    'queue' => env('RABBITMQ_QUEUE', 'weather.processing'),
    'retry_queue' => env('RABBITMQ_RETRY_QUEUE', 'weather.processing.retry'),
    'dlq' => env('RABBITMQ_DLQ', 'weather.processing.dlq'),

    /*
    |--------------------------------------------------------------------------
    | Routing keys
    |--------------------------------------------------------------------------
    |
    | Eventi koje ovaj servis obrađuje.
    |
    */

    'routing_keys' => [
        'weather_snapshot_fetched' => 'weather.snapshot.fetched',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry podešavanja
    |--------------------------------------------------------------------------
    |
    | TTL retry queue-a i maksimalan broj pokušaja obrade.
    |
    */

    'retry_ttl_ms' => (int) env('RABBITMQ_RETRY_TTL_MS', 5000),
    'max_retries' => (int) env('RABBITMQ_MAX_RETRIES', 5),
];
