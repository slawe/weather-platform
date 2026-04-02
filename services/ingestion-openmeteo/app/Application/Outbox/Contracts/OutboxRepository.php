<?php

namespace App\Application\Outbox\Contracts;

use App\Application\Outbox\DTO\OutboxMessageData;

/**
 * Repository interfejs za rad sa outbox porukama.
 *
 * Application sloj ne treba da zna kako se outbox poruke čuvaju,
 * već samo da može da ih sačuva i kasnije da ih publisher preuzme.
 */
interface OutboxRepository
{
    /**
     * Čuva jednu ili više outbox poruka.
     *
     * @param OutboxMessageData[] $messages
     */
    public function storeMany(array $messages): void;

    /**
     * Uzimа pending poruke koje su spremne za publish i zaključava ih za trenutni proces.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lockPendingBatch(int $limit): array;

    /**
     * Markira poruku kao uspešno poslatu.
     */
    public function markAsPublished(int $id): void;

    /**
     * Markira poruku kao neuspešnu i zakazuje sledeći pokušaj.
     */
    public function markAsFailed(int $id, string $error, int $attempts, int $maxAttempts): void;
}
