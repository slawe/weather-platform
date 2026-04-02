<?php

namespace App\Domain\Shared\Event;

use App\Application\Weather\DTO\WeatherSnapshotData;
use App\Domain\Weather\Location;
use Ramsey\Uuid\Uuid;

final readonly class WeatherSnapshotFetched implements DomainEvent
{
    private string $id;
    private string $occurredAtValue;

    public function __construct(
        private Location $location,
        private WeatherSnapshotData $snapshotData,
    ) {
        $this->id = Uuid::uuid7()->toString();
        $this->occurredAtValue = now()->toIso8601String();
    }

    /**
     * @inheritDoc
     */
    public function eventId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function eventName(): string
    {
        return 'weather.snapshot.fetched';
    }

    /**
     * @inheritDoc
     */
    public function eventVersion(): int
    {
        return 1;
    }

    /**
     * @inheritDoc
     */
    public function occurredAt(): string
    {
        return $this->occurredAtValue;
    }

    /**
     * @inheritDoc
     */
    public function payload(): array
    {
        return $this->snapshotData->toArray();
    }

    /**
     * Lokacija kojoj event pripada.
     * @return Location
     */
    public function location(): Location
    {
        return $this->location;
    }
}
