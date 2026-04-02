<?php

namespace App\Application\Weather\Contracts;

use App\Application\Messaging\DTO\MessageMetadata;
use App\Application\Weather\DTO\WeatherSnapshotPayload;

/**
 * Repository interfejs za read model vremenskih snapshot-a.
 *
 * Handler ne treba da zna kako se snapshot čuva, već samo da postoji
 * apstrakcija koja to radi.
 */
interface WeatherSnapshotRepository
{
    /**
     * Čuva obrađen vremenski snapshot u lokalni read model.
     */
    public function store(MessageMetadata $metadata, WeatherSnapshotPayload $payload): void;
}
