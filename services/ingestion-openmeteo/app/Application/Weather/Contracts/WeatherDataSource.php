<?php

namespace App\Application\Weather\Contracts;

use App\Application\Weather\DTO\WeatherSnapshotData;
use App\Domain\Weather\Location;

/**
 * Ovaj interfejs predstavlja apstrakciju nad izvorom vremenskih podataka.
 */
interface WeatherDataSource
{
    /**
     * Dohvata trenutni vremenski snapshot za zadatu lokaciju.
     */
    public function fetchCurrentWeather(Location $location): WeatherSnapshotData;
}
