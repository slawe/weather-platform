<?php

namespace App\Application\Messaging\DTO;

/**
 * DTO koji predstavlja jednu deserializovanu incoming integration poruku.
 *
 * Ovaj objekat je canonical representation poruke unutar processing servisa,
 * nezavisno od toga kako je poruka stigla sa RabbitMQ-a.
 */
final readonly class IncomingMessage
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public MessageMetadata $metadata,
        public array $payload,
    ) {
    }

    /**
     * Vraća event ID poruke.
     */
    public function eventId(): string
    {
        return $this->metadata->eventId;
    }

    /**
     * Vraća event name poruke.
     */
    public function eventName(): string
    {
        return $this->metadata->eventName;
    }

    /**
     * Vraća payload poruke.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }
}
