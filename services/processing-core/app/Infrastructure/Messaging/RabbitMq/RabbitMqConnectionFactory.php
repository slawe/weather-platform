<?php

namespace App\Infrastructure\Messaging\RabbitMq;

use PhpAmqpLib\Connection\AMQPStreamConnection;

/**
 * Fabrika za RabbitMQ konekciju u processing servisu.
 */
final class RabbitMqConnectionFactory
{
    /**
     * Kreira novu RabbitMQ konekciju na osnovu konfiguracije.
     */
    public function make(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            (string) config('rabbitmq.host'),
            (int) config('rabbitmq.port'),
            (string) config('rabbitmq.user'),
            (string) config('rabbitmq.password'),
            (string) config('rabbitmq.vhost'),
        );
    }
}
