<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Outbox\Contracts\OutboxRepository;
use App\Application\Outbox\DTO\OutboxMessageData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use JsonException;
use RuntimeException;

/**
 * Eloquent implementacija repository-ja za outbox poruke.
 *
 * Ovaj repository je zadužen za:
 *  - bulk upis novih outbox poruka
 *  - zaključavanje pending poruka za publish
 *  - promenu statusa posle publish pokušaja
 */
class EloquentOutboxRepository implements OutboxRepository
{
    /**
     * Čuva jednu ili više outbox poruka.
     *
     * @param OutboxMessageData[] $messages
     */
    public function storeMany(array $messages): void
    {
        if ($messages === []) return;

        $rows = array_map(
            fn (OutboxMessageData $message): array => $this->mapMessageToRow($message),
            $messages
        );

        foreach ($rows as $row) {
            DB::statement(
                <<<'SQL'
INSERT INTO outbox_messages (
    event_id,
    event_name,
    event_version,
    routing_key,
    deduplication_key,
    payload,
    headers,
    status,
    attempts,
    available_at,
    published_at,
    last_error,
    created_at,
    updated_at
) VALUES (
    ?,
    ?,
    ?,
    ?,
    ?,
    ?::jsonb,
    ?::jsonb,
    ?,
    ?,
    ?,
    ?,
    ?,
    ?,
    ?
)
ON CONFLICT (deduplication_key) DO UPDATE SET
    event_id = EXCLUDED.event_id,
    event_name = EXCLUDED.event_name,
    event_version = EXCLUDED.event_version,
    routing_key = EXCLUDED.routing_key,
    payload = EXCLUDED.payload,
    headers = EXCLUDED.headers,
    status = 'pending',
    attempts = 0,
    available_at = EXCLUDED.available_at,
    published_at = NULL,
    last_error = NULL,
    updated_at = EXCLUDED.updated_at
WHERE outbox_messages.payload::jsonb->'payload' IS DISTINCT FROM EXCLUDED.payload::jsonb->'payload'
SQL,
                [
                    $row['event_id'],
                    $row['event_name'],
                    $row['event_version'],
                    $row['routing_key'],
                    $row['deduplication_key'],
                    $row['payload'],
                    $row['headers'],
                    $row['status'],
                    $row['attempts'],
                    $row['available_at'],
                    $row['published_at'],
                    $row['last_error'],
                    $row['created_at'],
                    $row['updated_at'],
                ],
            );
        }
    }

    /**
     * Uzimа pending poruke koje su spremne za publish i zaključava ih za trenutni proces.
     *
     * @return array<int, array<string, mixed>>
     */
    public function lockPendingBatch(int $limit): array
    {
        return DB::transaction(function () use ($limit): array {
            $rows = DB::table('outbox_messages')
                ->where('status', 'pending')
                ->where(function ($query) {
                    $query->whereNull('available_at')
                        ->orWhere('available_at', '<=', now());
                })
                ->orderBy('id')
                ->lock('FOR UPDATE SKIP LOCKED')
                ->limit($limit)
                ->get();

            if ($rows->isEmpty()) return [];

            foreach ($rows as $row) {
                DB::table('outbox_messages')
                    ->where('id', $row->id)
                    ->update([
                        'attempts' => (int) $row->attempts + 1,
                        'updated_at' => now(),
                    ]);
            }

            $ids = $rows->pluck('id')->toArray();

            return DB::table('outbox_messages')
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->get()
                ->map(fn (object $row): array => (array) $row)
                ->all();
        });
    }

    /**
     * Markira poruku kao uspešno poslatu.
     */
    public function markAsPublished(int $id): void
    {
        DB::table('outbox_messages')
            ->where('id', $id)
            ->update([
                'status' => 'published',
                'published_at' => CarbonImmutable::now(),
                'last_error' => null,
                'updated_at' => now(),
            ]);
    }

    /**
     * Markira poruku kao neuspešnu i zakazuje sledeći pokušaj.
     */
    public function markAsFailed(int $id, string $error, int $attempts, int $maxAttempts): void
    {
        $backoffSeconds = min(60, 2 ** min(6, $attempts));

        DB::table('outbox_messages')
            ->where('id', $id)
            ->update([
                'status' => $attempts >= $maxAttempts ? 'failed' : 'pending',
                'available_at' => CarbonImmutable::now()->addSeconds($backoffSeconds),
                'last_error' => $error,
                'updated_at' => now(),
            ]);
    }

    /**
     * Mapira application DTO objekat u array za upis u bazu.
     *
     * @param OutboxMessageData $message
     * @return array
     */
    private function mapMessageToRow(OutboxMessageData $message): array
    {
        try {
            return [
                'event_id' => $message->eventId,
                'event_name' => $message->eventName,
                'event_version' => $message->eventVersion,
                'routing_key' => $message->routingKey,
                'deduplication_key' => $message->deduplicationKey,
                'payload' => $this->encodeJson($message->payload),
                'headers' => $message->headers !== null ? $this->encodeJson($message->headers) : null,
                'status' => $message->status,
                'attempts' => $message->attempts,
                'available_at' => $message->availableAt,
                'published_at' => $message->publishedAt,
                'last_error' => $message->lastError,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        } catch (JsonException $e) {
            throw new RuntimeException('Neuspešna JSON serializacija outbox poruke.', 0, $e);
        }
    }

    /**
     * Pretvara niz u JSON string za upis u JSON kolonu baze.
     *
     * @param array<string, mixed> $data
     *
     * @throws JsonException
     */
    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
