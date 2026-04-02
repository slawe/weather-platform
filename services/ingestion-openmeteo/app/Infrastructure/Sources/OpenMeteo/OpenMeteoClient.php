<?php

namespace App\Infrastructure\Sources\OpenMeteo;

use App\Domain\Weather\Location;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

/**
 * Klijent zadužen za komunikaciju sa Open-Meteo API-jem.
 *
 * Njegova odgovornost je isključivo HTTP poziv i vraćanje raw odgovora.
 * Mapiranje odgovora u naš interni DTO nije posao ove klase.
 */
final readonly class OpenMeteoClient
{
    public function __construct(private HttpFactory $http) {}

    public function fetchCurrentWeather(Location $location): array
    {
        $baseUrl = rtrim(config('weather.providers.open-meteo.base_url'), '/');
        $currentFields = config('weather.providers.open-meteo.current');
        $timezone = config('weather.providers.open-meteo.timezone', 'auto');

        $response = $this->http
            ->timeout(15)
            ->acceptJson()
            ->get("{$baseUrl}/forecast", [
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'current' => implode(',', $currentFields),
                'timezone' => $timezone,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                sprintf(
                    'Open-Meteo API greška. HTTP status: %s, lokacija: %s',
                    $response->status(),
                    $location->name
                )
            );
        }

        return $response->json();
    }
}
