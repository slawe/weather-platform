<?php

namespace App\Application\Weather\Actions;

use App\Application\Outbox\Contracts\OutboxRepository;
use App\Application\Outbox\Services\OutboxMessageFactory;
use App\Application\Weather\Contracts\LocationRepository;
use App\Application\Weather\Contracts\WeatherDataSource;
use App\Domain\Shared\Event\WeatherSnapshotFetched;
use App\Domain\Weather\WeatherIngestion;
use Illuminate\Support\Facades\DB;

/**
 * Use case koji prolazi kroz sve aktivne lokacije, dohvatа vremenske podatke
 * sa izabranog source-a i pretvara ih u outbox poruke.
 *
 * Bitno je da application sloj orkestrira proces, dok:
 * - repository zna kako se čita iz baze
 * - data source zna kako se poziva eksterni API
 * - outbox factory zna kako se pravi integration-ready poruka
 */
final readonly class FetchWeatherForLocationsAction
{
    public function __construct(
        private LocationRepository $locationRepository,
        private WeatherDataSource $weatherDataSource,
        private OutboxMessageFactory $outboxMessageFactory,
        private OutboxRepository $outboxRepository,
    ) {}

    /**
     * Pokrece ingestion za sve aktivne lokacije.
     *
     * Proces:
     * 1. Dohvata vremenske podatke sa izabranog source-a
     * 2. Kreira event za svaku lokaciju
     * 3. Kreira outbox poruke
     * 4. Sprema poruke u bazu
     *
     * @return int - broj kreiranih outbox poruka
     */
    public function execute(): int
    {
        $locations = $this->locationRepository->getActiveLocations();

        if ($locations === []) return 0;

        $outboxMessages = [];

        foreach ($locations as $location) {
            $snapshot = $this->weatherDataSource->fetchCurrentWeather($location);

            $ingestion = WeatherIngestion::forLocation($location);
            $ingestion->recordFetchedSnapshot($snapshot);

            $events = $ingestion->releaseEvents();

            $messages = $this->outboxMessageFactory->fromDomainEvents(
                events: $events,
                producer: 'ingestion-openmeteo',
            );

            array_push($outboxMessages, ...$messages);
        }

        DB::transaction(function () use ($outboxMessages):void {
            $this->outboxRepository->storeMany($outboxMessages);
        });

        return count($outboxMessages);
    }
}
