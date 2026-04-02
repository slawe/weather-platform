<?php

namespace App\Console\Commands;

use App\Application\Weather\Actions\FetchWeatherForLocationsAction;
use Illuminate\Console\Command;
use Throwable;

/**
 * Artisan komanda koja pokreće ingestion vremenskih podataka za sve aktivne lokacije.
 *
 * Ova komanda je tanka: ne sadrži biznis logiku, već samo delegira posao
 * application use case-u i prikazuje rezultat korisniku.
 */
final class FetchWeatherCommand extends Command
{
    /**
     * Naziv Artisan komande.
     *
     * Primer:
     * php artisan weather:fetch
     */
    protected $signature = 'weather:fetch';

    /**
     * Opis komande.
     */
    protected $description = 'Dohvata trenutne vremenske podatke za aktivne lokacije i puni outbox.';

    public function __construct(
        private readonly FetchWeatherForLocationsAction $action,
    ) {
        parent::__construct();
    }

    /**
     * Pokreće ingestion proces.
     *
     * @return int
     */
    public function handle(): int
    {
        try {
            $count = $this->action->execute();

            $this->info("Uspešno kreirano outbox poruka: {$count}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Greška prilikom ingestion procesa: ' . $e->getMessage());

            report($e);

            return self::FAILURE;
        }
    }
}
