<?php

namespace App\Infrastructure\Messaging\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Deklariše RabbitMQ topologiju koju processing-core poseduje.
 */
final readonly class RabbitMqTopology
{
    public function __construct(private RabbitMqConnectionFactory $connectionFactory) {}

    /**
     * Deklariše topologiju koristeći novu RabbitMQ konekciju.
     */
    public function setup(): void
    {
        $connection = $this->connectionFactory->make();
        $channel = $connection->channel();

        $this->declare($channel);

        $channel->close();
        $connection->close();
    }

    /**
     * Deklariše exchange, glavnu queue, retry queue i DLQ.
     */
    public function declare(AMQPChannel $channel): void
    {
        $config = $this->config();

        $channel->exchange_declare(
            $config['exchange'],
            $config['exchange_type'],
            false,
            true,
            false
        );

        $channel->queue_declare($config['queue'], false, true, false, false);
        $channel->queue_bind($config['queue'], $config['exchange'], $config['routing_key']);

        $retryArguments = new AMQPTable([
            'x-message-ttl' => (int) $config['retry_ttl_ms'],
            'x-dead-letter-exchange' => $config['exchange'],
            'x-dead-letter-routing-key' => $config['routing_key'],
        ]);

        $channel->queue_declare(
            $config['retry_queue'],
            false,
            true,
            false,
            false,
            false,
            $retryArguments
        );

        $channel->queue_declare($config['dlq'], false, true, false, false);
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return [
            'exchange' => (string) config('rabbitmq.exchange'),
            'exchange_type' => (string) config('rabbitmq.exchange_type', 'topic'),
            'queue' => (string) config('rabbitmq.queue'),
            'retry_queue' => (string) config('rabbitmq.retry_queue'),
            'dlq' => (string) config('rabbitmq.dlq'),
            'retry_ttl_ms' => (int) config('rabbitmq.retry_ttl_ms'),
            'routing_key' => (string) (config('rabbitmq.routing_keys.weather_snapshot_fetched') ?? 'weather.snapshot.fetched'),
        ];
    }
}
