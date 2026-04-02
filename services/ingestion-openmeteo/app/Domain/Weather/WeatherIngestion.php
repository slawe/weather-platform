<?php

namespace App\Domain\Weather;

use App\Application\Weather\DTO\WeatherSnapshotData;
use App\Domain\Shared\Event\RecordsEvents;
use App\Domain\Shared\Event\WeatherSnapshotFetched;

/**
 * Agregat koji predstavlja proces ingestije vremenskih podataka za jednu lokaciju.
 *
 * Njegova uloga je da bude mesto u domenu gde nastaje događaj da je
 * vremenski snapshot uspešno preuzet i spreman za dalje procesiranje.
 */
final class WeatherIngestion
{
    use RecordsEvents;

    /**
     * WeatherIngestion constructor.
     *
     * @param Location $location
     */
    public function __construct(private readonly Location $location) {}

    /**
     * Kreira agregat za zadatu lokaciju.
     *
     * @param Location $location
     * @return self
     */
    public static function forLocation(Location $location): self
    {
        return new self($location);
    }

    /**
     * Beleži događaj da je za lokaciju uspešno preuzet i normalizovan snapshot.
     *
     *  Ovaj metod je namerno u domenu, da event ne nastaje u application sloju,
     *  već u agregatu koji predstavlja domen proces ingestije.
     *
     * @param WeatherSnapshotData $snapshotData
     * @return void
     */
    public function recordFetchedSnapshot(WeatherSnapshotData $snapshotData): void
    {
        $this->recordEvent(new WeatherSnapshotFetched(
            location: $this->location,
            snapshotData: $snapshotData,
        ));
    }
}
