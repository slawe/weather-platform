<?php

namespace App\Infrastructure\Messaging\RabbitMq;

use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Factory za kreiranje RabbitMQ konekcije.
 *
 * Ideja je da RabbitMQ connection detalje držimo na jednom mestu,
 * umesto da ih razvlačimo po komandama i servisima.
 */
final class RabbitMqConnectionFactory
{
    /**
     * Kreira novu RabbitMQ konekciju na osnovu konfiguracije aplikacije.
     */
    public function make(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            host: (string) config('rabbitmq.host'),
            port: (int) config('rabbitmq.port'),
            user: (string) config('rabbitmq.user'),
            password: (string) config('rabbitmq.password'),
            vhost: (string) config('rabbitmq.vhost'),
        );
    }
}
