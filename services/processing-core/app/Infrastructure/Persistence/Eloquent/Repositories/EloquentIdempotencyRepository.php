<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Messaging\Contracts\IdempotencyRepository;
use App\Infrastructure\Persistence\Eloquent\Models\ConsumedEventModel;

/**
 * Eloquent implementacija repository-ja za idempotency mehanizam.
 */
class EloquentIdempotencyRepository implements IdempotencyRepository
{
    /**
     * Proverava da li je dati event već obrađen.
     */
    public function alreadyProcessed(string $eventId): bool
    {
        return ConsumedEventModel::query()
            ->where('event_id', $eventId)
            ->exists();
    }

    /**
     * Beleži da je event uspešno obrađen.
     */
    public function markProcessed(string $eventId, string $eventName): void
    {
        ConsumedEventModel::query()->create([
            'event_id' => $eventId,
            'event_name' => $eventName,
            'consumed_at' => now(),
        ]);
    }
}
