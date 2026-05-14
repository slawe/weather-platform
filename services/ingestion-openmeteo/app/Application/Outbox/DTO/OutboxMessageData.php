<?php

namespace App\Application\Outbox\DTO;

/**
 * DTO koji predstavlja jednu outbox poruku spremnu za čuvanje u bazi.
 *
 * Ovaj objekat je most između domain/application sloja i persistence sloja.
 * Repository ne treba da zna ništa o domain eventima, već dobija već
 * pripremljene podatke koje treba upisati u outbox tabelu.
 */
final readonly class OutboxMessageData
{
    public function __construct(
        public string $eventId,
        public string $eventName,
        public int $eventVersion,
        public string $routingKey,
        public string $deduplicationKey,
        public array $payload,
        public ?array $headers = null,
        public string $status = 'pending',
        public int $attempts = 0,
        public ?string $availableAt = null,
        public ?string $publishedAt = null,
        public ?string $lastError = null,
    ) {
    }

    /**
     * Pretvara DTO u niz vrednosti pogodan za upis u bazu.
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->eventName,
            'event_version' => $this->eventVersion,
            'routing_key' => $this->routingKey,
            'deduplication_key' => $this->deduplicationKey,
            'payload' => $this->payload,
            'headers' => $this->headers,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'available_at' => $this->availableAt,
            'published_at' => $this->publishedAt,
            'last_error' => $this->lastError,
        ];
    }
}
