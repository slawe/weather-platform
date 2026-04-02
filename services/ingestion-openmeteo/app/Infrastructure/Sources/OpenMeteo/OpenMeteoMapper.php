<?php

namespace App\Infrastructure\Sources\OpenMeteo;

use App\Application\Weather\DTO\WeatherSnapshotData;
use App\Domain\Weather\Location;
use RuntimeException;

/**
 * Mapper prevodi raw Open-Meteo odgovor u naš normalizovani WeatherSnapshotData DTO.
 *
 * Ova klasa je jedino mesto koje zna kako izgleda Open-Meteo JSON struktura.
 * Time ostatak sistema ostaje izolovan od spoljnog API formata.
 */
final class OpenMeteoMapper
{
    /**
     * Pretvara raw Open-Meteo odgovor u naš interni DTO.
     *
     * @param array<string, mixed> $response
     */
    public function mapCurrentWeather(array $response, Location $location): WeatherSnapshotData
    {
        $current = $response['current'] ?? null;

        if (!is_array($current)) {
            throw new RuntimeException('Open-Meteo odgovor nema "current" sekciju.');
        }

        return new WeatherSnapshotData(
            locationId: $location->id,
            city: $location->name,
            country: $location->country,
            latitude: $location->latitude,
            longitude: $location->longitude,
            temperatureC: $this->getRequiredFloat($current, 'temperature_2m'),
            windSpeedKmh: $this->getRequiredFloat($current, 'wind_speed_10m'),
            weatherCode: $this->getRequiredInt($current, 'weather_code'),
            observedAt: $this->getRequiredString($current, 'time'),
            source: 'open-meteo',
        );
    }

    /**
     * Vraća obaveznu vrednost za zadati ključ.

     * Ako ključ ne postoji u nizu, baca izuzetak sa jasnom porukom greške.
     *
     * @param array<string, mixed> $data
     */
    private function getRequiredValue(array $data, string $key): mixed
    {
        return $data[$key] ?? throw new RuntimeException(
            "Open-Meteo odgovor nema obavezno polje [{$key}]."
        );
    }

    /**
     * Vraća obaveznu float vrednost za zadati ključ.
     *
     * Bacamo grešku ako vrednost nije numerička, kako bismo izbegli
     * tihu i opasnu PHP konverziju u 0.
     *
     * @param array<string, mixed> $data
     */
    private function getRequiredFloat(array $data, string $key): float
    {
        $value = $this->getRequiredValue($data, $key);

        if (!is_numeric($value)) {
            throw new RuntimeException("Polje [{$key}] mora biti numeričko.");
        }

        return (float) $value;
    }

    /**
     * Vraća obaveznu integer vrednost za zadati ključ.
     *
     * @param array<string, mixed> $data
     */
    private function getRequiredInt(array $data, string $key): int
    {
        $value = $this->getRequiredValue($data, $key);

        if (!is_numeric($value)) {
            throw new RuntimeException("Polje [{$key}] mora biti numeričko.");
        }

        return (int) $value;
    }

    /**
     * Vraća obaveznu string vrednost za zadati ključ.
     *
     * @param array<string, mixed> $data
     */
    private function getRequiredString(array $data, string $key): string
    {
        $value = $this->getRequiredValue($data, $key);

        if (!is_scalar($value)) {
            throw new RuntimeException("Polje [{$key}] mora biti skalarna vrednost.");
        }

        $stringValue = trim((string) $value);

        if ($stringValue === '') {
            throw new RuntimeException("Polje [{$key}] ne sme biti prazno.");
        }

        return $stringValue;
    }
}
