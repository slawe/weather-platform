<?php

namespace App\Application\Weather\DTO;

/**
 * DTO koji predstavlja business payload eventa weather.snapshot.fetched.
 *
 * Ovo je tipizovana verzija payload-a koju koristi handler, kako ne bi
 * radio direktno sa sirovim nizovima.
 */
final readonly class WeatherSnapshotPayload
{
    public function __construct(
        public string $locationId,
        public string $city,
        public string $country,
        public float $latitude,
        public float $longitude,
        public float $temperatureC,
        public float $windSpeedKmh,
        public int $weatherCode,
        public string $observedAt,
        public string $source,
    ) {
    }

    /**
     * Kreira DTO iz payload niza.
     *
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            locationId: (string) $payload['location_id'],
            city: (string) $payload['city'],
            country: (string) $payload['country'],
            latitude: (float) $payload['latitude'],
            longitude: (float) $payload['longitude'],
            temperatureC: (float) $payload['temperature_c'],
            windSpeedKmh: (float) $payload['wind_speed_kmh'],
            weatherCode: (int) $payload['weather_code'],
            observedAt: (string) $payload['observed_at'],
            source: (string) $payload['source'],
        );
    }

    /**
     * Vraća DTO kao niz.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'location_id' => $this->locationId,
            'city' => $this->city,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'temperature_c' => $this->temperatureC,
            'wind_speed_kmh' => $this->windSpeedKmh,
            'weather_code' => $this->weatherCode,
            'observed_at' => $this->observedAt,
            'source' => $this->source,
        ];
    }
}
