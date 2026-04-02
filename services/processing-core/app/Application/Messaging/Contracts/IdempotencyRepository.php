<?php

namespace App\Application\Messaging\Contracts;

/**
 * Repository contract za idempotency mehanizam.
 *
 * Processing servis preko ovog contract-a proverava da li je event već obrađen
 * i beleži uspešno obrađene evente.
 */
interface IdempotencyRepository
{
    /**
     * Proverava da li je dati event već obrađen.
     */
    public function alreadyProcessed(string $eventId): bool;

    /**
     * Beleži da je event uspešno obrađen.
     */
    public function markProcessed(string $eventId, string $eventName): void;
}
