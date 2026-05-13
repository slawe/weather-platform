<?php

namespace App\Infrastructure\Messaging\RabbitMq;

use App\Application\Messaging\Services\IdempotencyService;
use App\Application\Messaging\Services\MessageRouter;
use App\Infrastructure\Messaging\Serialization\IncomingMessageDeserializer;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Glavni RabbitMQ consumer processing servisa.
 *
 * Ova klasa:
 * - postavlja RabbitMQ topology
 * - prima poruke sa glavne queue
 * - deserializuje integration event envelope
 * - routuje poruku do odgovarajućeg handlera
 * - obezbeđuje retry i DLQ ponašanje
 */
final class RabbitMqConsumer
{
    private bool $running = true;

    public function __construct(
        private readonly RabbitMqConnectionFactory $connectionFactory,
        private readonly IncomingMessageDeserializer $deserializer,
        private readonly MessageRouter $messageRouter,
        private readonly IdempotencyService $idempotencyService,
        private readonly RabbitMqMessageRepublisher $messageRepublisher,
    ) {
    }

    /**
     * Pokreće consume petlju.
     */
    public function consume(): void
    {
        $this->registerSignalHandlers();

        $connection = $this->connectionFactory->make();
        $channel = $connection->channel();

        $this->declareTopology($channel);

        $queue = (string) config('rabbitmq.queue');

        // Obrada jedne poruke po workeru smanjuje rizik od haotične paralelne obrade.
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            fn (AMQPMessage $message) => $this->handleMessage($channel, $message),
        );

        while ($this->running) {
            try {
                $channel->wait(null, false, 5);
            } catch (AMQPTimeoutException) {
                // Nema poruka u poslednjih 5 sekundi. Nastavljamo petlju.
            } catch (\Throwable $e) {
                // Ctrl+C može prekinuti stream_select i to tretiramo kao uredan shutdown.
                if (str_contains($e->getMessage(), 'Interrupted system call')) {
                    break;
                }

                throw $e;
            }
        }

        $channel->close();
        $connection->close();
    }

    /**
     * Deklariše exchange, glavnu queue, retry queue i DLQ.
     */
    private function declareTopology(AMQPChannel $channel): void
    {
        $config = $this->topologyConfig();

        $channel->exchange_declare(
            $config['exchange'],
            $config['exchange_type'],
            false,
            true,
            false
        );

        $channel->queue_declare($config['queue'], false, true, false, false);
        $channel->queue_bind($config['queue'], $config['exchange'], $config['routing_key']);

        $retryArguments = new AMQPTable([
            'x-message-ttl' => (int) $config['retry_ttl_ms'],
            'x-dead-letter-exchange' => $config['exchange'],
            'x-dead-letter-routing-key' => $config['routing_key'],
        ]);

        $channel->queue_declare(
            $config['retry_queue'],
            false,
            true,
            false,
            false,
            false,
            $retryArguments
        );

        $channel->queue_declare($config['dlq'], false, true, false, false);
    }

    /**
     * Obrađuje jednu RabbitMQ poruku.
     */
    private function handleMessage(AMQPChannel $channel, AMQPMessage $message): void
    {
        $body = $message->getBody();
        $properties = $message->get_properties();

        try {
            $incomingMessage = $this->deserializer->deserialize(
                body: $body,
                headers: $this->extractHeaders($properties),
            );

            $handler = $this->messageRouter->resolve($incomingMessage->eventName());

            $this->idempotencyService->handleOnce($incomingMessage, $handler);

            $this->ack($channel, $message);
        } catch (\Throwable $exception) {
            $this->handleFailure($channel, $message, $body, $properties, $exception);
        }
    }

    /**
     * Obrada neuspešne poruke kroz retry ili DLQ tok.
     *
     * @param array<string, mixed> $properties
     */
    private function handleFailure(
        AMQPChannel $channel,
        AMQPMessage $message,
        string $body,
        array $properties,
        \Throwable $exception
    ): void {
        $retryCount = $this->currentRetryCount($properties);
        $nextRetryCount = $retryCount + 1;
        $maxRetries = (int) config('rabbitmq.max_retries');
        $retryQueue = (string) config('rabbitmq.retry_queue');
        $dlq = (string) config('rabbitmq.dlq');

        Log::warning('Greška pri obradi incoming poruke.', [
            'error' => $exception->getMessage(),
            'retry_count' => $retryCount,
            'event_id' => $properties['message_id'] ?? null,
            'event_name' => $properties['type'] ?? null,
        ]);

        if ($nextRetryCount >= $maxRetries) {
            $this->messageRepublisher->publishToQueue(
                channel: $channel,
                queueName: $dlq,
                body: $body,
                originalProperties: $properties,
                retryCount: $nextRetryCount,
            );

            $this->ack($channel, $message);

            return;
        }

        $this->messageRepublisher->publishToQueue(
            channel: $channel,
            queueName: $retryQueue,
            body: $body,
            originalProperties: $properties,
            retryCount: $nextRetryCount,
        );

        $this->ack($channel, $message);
    }

    /**
     * Potvrđuje uspešno preuzimanje originalne RabbitMQ poruke.
     */
    private function ack(AMQPChannel $channel, AMQPMessage $message): void
    {
        $deliveryTag = $message->delivery_info['delivery_tag'] ?? null;

        if ($deliveryTag !== null) {
            $channel->basic_ack($deliveryTag);
        }
    }

    /**
     * Vraća trenutni retry broj iz RabbitMQ headera.
     *
     * @param array<string, mixed> $properties
     */
    private function currentRetryCount(array $properties): int
    {
        $headers = $this->extractHeaders($properties);

        return (int) ($headers['x-retry-count'] ?? 0);
    }

    /**
     * Vraća native headers iz application_headers ako postoje.
     *
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    private function extractHeaders(array $properties): array
    {
        $applicationHeaders = $properties['application_headers'] ?? null;

        if ($applicationHeaders instanceof AMQPTable) {
            return $applicationHeaders->getNativeData();
        }

        return [];
    }

    /**
     * Centralizuje RabbitMQ topology konfiguraciju na jednom mestu.
     *
     * @return array<string, mixed>
     */
    private function topologyConfig(): array
    {
        return [
            'exchange' => (string) config('rabbitmq.exchange'),
            'exchange_type' => (string) config('rabbitmq.exchange_type', 'topic'),
            'queue' => (string) config('rabbitmq.queue'),
            'retry_queue' => (string) config('rabbitmq.retry_queue'),
            'dlq' => (string) config('rabbitmq.dlq'),
            'retry_ttl_ms' => (int) config('rabbitmq.retry_ttl_ms'),
            'routing_key' => (string) (config('rabbitmq.routing_keys.weather_snapshot_fetched') ?? 'weather.snapshot.fetched'),
        ];
    }

    /**
     * Registruje signal handlere kako bi consumer mogao uredno da se ugasi.
     */
    private function registerSignalHandlers(): void
    {
        if (!function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGINT, function (): void {
            $this->running = false;
        });

        pcntl_signal(SIGTERM, function (): void {
            $this->running = false;
        });
    }
}
