<?php

namespace App\Infrastructure\Sources\OpenMeteo;

use App\Application\Weather\Contracts\WeatherDataSource;
use App\Application\Weather\DTO\WeatherSnapshotData;
use App\Domain\Weather\Location;

/**
 * Implementacija WeatherDataSource contract-a za Open-Meteo provider.
 *
 * Ova klasa spaja:
 * - HTTP klijent koji vraća raw odgovor
 * - mapper koji taj odgovor prevodi u naš interni DTO
 */
final readonly class OpenMeteoWeatherDataSource implements WeatherDataSource
{
    /**
     * OpenMeteoWeatherDataSource constructor.
     *
     * @param OpenMeteoClient $client
     * @param OpenMeteoMapper $mapper
     */
    public function __construct(
        private OpenMeteoClient $client,
        private OpenMeteoMapper $mapper,
    ) {
    }

    /**
     * Dohvata trenutne vremenske podatke za zadatu lokaciju.
     *
     * @param Location $location
     * @return WeatherSnapshotData
     */
    public function fetchCurrentWeather(Location $location): WeatherSnapshotData
    {
        // kreira HTTP zahtev za trenutne vremenske podatke
        $response = $this->client->fetchCurrentWeather($location);
        // mapira odgovor na DTO
        return $this->mapper->mapCurrentWeather($response, $location);
    }
}
