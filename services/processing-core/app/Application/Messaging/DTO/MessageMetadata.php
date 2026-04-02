<?php

namespace App\Application\Messaging\DTO;

/**
 * DTO koji predstavlja meta podatke incoming poruke.
 *
 * Ovaj objekat odvaja tehničke informacije o eventu od samog business payload-a.
 */
final readonly class MessageMetadata
{
    public function __construct(
        public string $eventId,
        public string $eventName,
        public int $eventVersion,
        public string $occurredAt,
        public string $producer,
        public array $headers = [],
    ) {
    }

    /**
     * Vraća metadata podatke kao niz.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'event_name' => $this->eventName,
            'event_version' => $this->eventVersion,
            'occurred_at' => $this->occurredAt,
            'producer' => $this->producer,
            'headers' => $this->headers,
        ];
    }
}
