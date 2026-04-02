<?php

namespace App\Infrastructure\Messaging\RabbitMq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Zajednički helper za ponovno slanje poruke u zadatu RabbitMQ queue.
 *
 * Koristi se i za retry i za DLQ tok, kako ne bismo duplirali istu logiku
 * oko pakovanja poruke, prenosa metadata polja i rada sa headerima.
 */
final class RabbitMqMessageRepublisher
{
    /**
     * Publikuje poruku u zadatu queue uz ažurirane headere.
     *
     * @param array<string, mixed> $originalProperties
     */
    public function publishToQueue(
        AMQPChannel $channel,
        string $queueName,
        string $body,
        array $originalProperties,
        int $retryCount
    ): void {
        $headers = $this->extractHeaders($originalProperties);
        $headers['x-retry-count'] = $retryCount;

        $message = new AMQPMessage($body, [
            'content_type' => $originalProperties['content_type'] ?? 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $originalProperties['message_id'] ?? null,
            'type' => $originalProperties['type'] ?? null,
            'timestamp' => time(),
            'application_headers' => new AMQPTable($headers),
        ]);

        // Koristimo default exchange, pa je routing key zapravo ime queue-a.
        $channel->basic_publish($message, '', $queueName);
    }

    /**
     * Vraća native headers iz application_headers ako postoje.
     *
     * @param array<string, mixed> $originalProperties
     * @return array<string, mixed>
     */
    private function extractHeaders(array $originalProperties): array
    {
        $applicationHeaders = $originalProperties['application_headers'] ?? null;

        if ($applicationHeaders instanceof AMQPTable) {
            return $applicationHeaders->getNativeData();
        }

        return [];
    }
}
