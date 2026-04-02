<?php

namespace App\Application\Weather\DTO;

use App\Domain\Weather\Location;

/**
 * Ovaj DTO predstavlja naš interni, normalizovani model vremenskog snapshot-a.
 *
 * Bitno je da application sloj ne zavisi od raw strukture eksternog API-ja.
 * Provider može vratiti šta god želi, ali mi kroz sistem prosleđujemo samo
 * stabilan i jasno definisan DTO.
 */
final readonly class WeatherSnapshotData
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
     * Pretvara DTO u niz podataka pogodan za event payload ili upis u bazu.
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
