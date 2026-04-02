<?php

namespace App\Infrastructure\Messaging\RabbitMq;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Nizak infrastrukturni publisher za slanje poruka na RabbitMQ.
 *
 * Ova klasa ne zna ništa o domenu vremenskih podataka.
 * Njen posao je isključivo da pošalje već pripremljenu poruku na broker.
 */
final readonly class RabbitMqMessagePublisher
{
    public function __construct(private RabbitMqConnectionFactory $connectionFactory) {}

    /**
     * Publikuje jednu poruku na zadati exchange i routing key.
     *
     * @param string $routingKey
     * @param string $body
     * @param array $headers
     * @param string|null $messageId
     * @param string|null $type
     * @return void
     */
    public function publish(
        string $routingKey,
        string $body,
        array $headers = [],
        ?string $messageId = null,
        ?string $type = null
    ): void
    {
        $connection = $this->connectionFactory->make();
        $channel = $connection->channel();
        $exchange = config('rabbitmq.exchange');
        $exchangeType = config('rabbitmq.exchange_type');

        // osiguravamo da exchange postoji pre publish-a
        $channel->exchange_declare($exchange, $exchangeType, false, true, false);

        $message = new AMQPMessage($body, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $messageId,
            'type' => $type,
            'timestamp' => time(),
            'application_headers' => new AMQPTable($headers),
        ]);

        $channel->basic_publish($message, $exchange, $routingKey);

        $channel->close();
        $connection->close();
    }
}
