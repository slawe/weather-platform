<?php

namespace App\Application\Weather\Contracts;

use App\Domain\Weather\Location;

/**
 * Repository interfejs za rad sa lokacijama koje pratimo.
 */
interface LocationRepository
{
    /**
     * Vraća sve aktivne lokacije koje treba uključiti u ingestion proces.
     *
     * @return Location[]
     */
    public function getActiveLocations(): array;
}
