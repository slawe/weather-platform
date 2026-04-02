<?php

namespace App\Console\Commands;

use App\Application\Outbox\Contracts\OutboxRepository;
use App\Infrastructure\Messaging\RabbitMq\RabbitMqMessagePublisher;
use App\Infrastructure\Messaging\Serialization\IntegrationEventSerializer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use JsonException;
use Throwable;

/**
 * Komanda koja čita pending outbox poruke i publikuje ih na RabbitMQ.
 *
 * Ova komanda predstavlja odvojeni korak u outbox pattern-u:
 * - business/use case pravi outbox zapis
 * - publisher ga kasnije šalje na broker
 */
final class PublishOutboxMessagesCommand extends Command
{
    /**
     * Primer:
     * php artisan outbox:publish
     * php artisan outbox:publish --limit=50
     */
    protected $signature = 'outbox:publish {--limit=20 : Maksimalan broj poruka po jednoj rundi}';

    /**
     * Opis komande.
     */
    protected $description = 'Publikuje pending outbox poruke na RabbitMQ.';

    /**
     * Konstruktor.
     *
     * @param OutboxRepository $outboxRepository
     * @param IntegrationEventSerializer $serializer
     * @param RabbitMqMessagePublisher $publisher
     */
    public function __construct(
        private readonly OutboxRepository $outboxRepository,
        private readonly IntegrationEventSerializer $serializer,
        private readonly RabbitMqMessagePublisher $publisher,
    ) {
        parent::__construct();
    }

    /**
     * Pokreće publish outbox proces.
     *
     * @return int
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $maxAttempts = (int) env('RABBITMQ_MAX_ATTEMPTS', 20);

        $messages = $this->outboxRepository->lockPendingBatch($limit);

        if ($messages === []) {
            $this->info('Nema pending outbox poruka za publikaciju.');
            return self::SUCCESS;
        }

        $published = 0;

        foreach ($messages as $message) {
            try {
                $payload = $this->decodePayload($message['payload']);
                $body = $this->serializer->serialize($payload);

                $this->publisher->publish(
                    routingKey: (string) $message['routing_key'],
                    body: $body,
                    headers: $this->decodeHeaders($message['headers']),
                    messageId: (string) $message['event_id'],
                    type: (string) $message['event_name'],
                );

                $this->outboxRepository->markAsPublished((int) $message['id']);
                $published++;
            } catch (Throwable $e) {
                $attempts = (int) $message['attempts'];

                $this->outboxRepository->markAsFailed(
                    id: (int) $message['id'],
                    error: $e->getMessage(),
                    attempts: $attempts,
                    maxAttempts: $maxAttempts,
                );

                Log::error('Outbox publish failed', [
                    'outbox_id' => $message['id'],
                    'event_id' => $message['event_id'] ?? null,
                    'attempts' => $attempts,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Upsesno publikovano poruka: {$published}");
        return self::SUCCESS;
    }

    /**
     * Dekodira payload u array.
     *
     * @param mixed $payload
     * @return array
     * @throws JsonException
     */
    private function decodePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        return json_decode((string) $payload, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Dekodira headers u array.
     *
     * @param mixed $headers
     * @return array
     * @throws JsonException
     */
    private function decodeHeaders(mixed $headers): array
    {
        if ($headers === null) {
            return [];
        }

        if (is_array($headers)) {
            return $headers;
        }

        return json_decode((string) $headers, true, 512, JSON_THROW_ON_ERROR);
    }
}
