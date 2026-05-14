<?php

namespace App\Application\Outbox\Services;

use App\Application\Outbox\DTO\OutboxMessageData;
use App\Domain\Shared\Event\DomainEvent;
use App\Support\Messaging\RoutingKeyResolver;

/**
 * Ova klasa prevodi domain evente u outbox poruke koje će kasnije
 * publisher poslati na RabbitMQ.
 *
 * Time jasno razdvajamo:
 * - domain event (interna domen logika)
 * - outbox zapis (persistirani integration-ready zapis)
 */
final readonly class OutboxMessageFactory
{
    public function __construct(private RoutingKeyResolver $routingKeyResolver) {}

    /**
     * Kreira jednu outbox poruku iz domain eventa.
     */
    public function fromDomainEvent(DomainEvent $event, string $producer): OutboxMessageData
    {
        return new OutboxMessageData(
            eventId: $event->eventId(),
            eventName: $event->eventName(),
            eventVersion: $event->eventVersion(),
            routingKey: $this->routingKeyResolver->resolve($event->eventName()),
            deduplicationKey: $this->deduplicationKey($event),
            payload: [
                'event_id' => $event->eventId(),
                'event_name' => $event->eventName(),
                'event_version' => $event->eventVersion(),
                'occurred_at' => $event->occurredAt(),
                'producer' => $producer,
                'payload' => $event->payload(),
            ],
            headers: [
                'x-event-id' => $event->eventId(),
                'x-event-name' => $event->eventName(),
                'x-event-version' => $event->eventVersion(),
                'x-producer' => $producer,
            ],
            status: 'pending',
            attempts: 0,
            availableAt: now()->toIso8601String(),
        );
    }

    /**
     * Kreira stabilan key za latest-state outbox zapis.
     */
    private function deduplicationKey(DomainEvent $event): string
    {
        $payload = $event->payload();

        return implode(':', [
            $event->eventName(),
            (string) ($payload['location_id'] ?? ''),
            (string) ($payload['source'] ?? ''),
        ]);
    }

    /**
     * Kreira više outbox poruka iz niza domain eventa.
     *
     * @param DomainEvent[] $events
     * @return OutboxMessageData[]
     */
    public function fromDomainEvents(array $events, string $producer): array
    {
        return array_map(
            fn (DomainEvent $event) => $this->fromDomainEvent($event, $producer),
            $events
        );
    }
}
