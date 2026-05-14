<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Messaging\DTO\MessageMetadata;
use App\Application\Weather\Contracts\WeatherSnapshotRepository;
use App\Application\Weather\DTO\WeatherSnapshotPayload;
use App\Infrastructure\Persistence\Eloquent\Models\WeatherSnapshotModel;

class EloquentWeatherSnapshotRepository implements WeatherSnapshotRepository
{
    /**
     * Čuva ili ažurira najnoviji vremenski snapshot u lokalni read model.
     */
    public function store(MessageMetadata $metadata, WeatherSnapshotPayload $payload): void
    {
        WeatherSnapshotModel::query()->updateOrCreate(
            [
                'location_id' => $payload->locationId,
                'source' => $payload->source,
            ],
            [
                'event_id' => $metadata->eventId,
                'city' => $payload->city,
                'country' => $payload->country,
                'latitude' => $payload->latitude,
                'longitude' => $payload->longitude,
                'temperature_c' => $payload->temperatureC,
                'wind_speed_kmh' => $payload->windSpeedKmh,
                'weather_code' => $payload->weatherCode,
                'observed_at' => $payload->observedAt,
                'received_at' => now(),
            ],
        );
    }
}
