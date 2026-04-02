<?php

namespace App\Application\Messaging\Services;

use App\Application\Messaging\Contracts\MessageHandler;
use App\Application\Weather\Handlers\WeatherSnapshotFetchedHandler;
use InvalidArgumentException;

/**
 * Router koji na osnovu event name određuje koji handler treba da obradi poruku.
 *
 * Za v1 podržavamo jedan event type, ali struktura ostaje spremna za širenje.
 */
final readonly class MessageRouter
{
    /**
     * MessageRouter konstruktor.
     *
     * @param WeatherSnapshotFetchedHandler $weatherSnapshotFetchedHandler
     */
    public function __construct(private WeatherSnapshotFetchedHandler $weatherSnapshotFetchedHandler) {}

    /**
     * Vraća odgovarajući handler za dati event name.
     *
     * @param string $eventName
     * @return MessageHandler
     */
    public function resolve(string $eventName): MessageHandler
    {
        return match ($eventName) {
            'weather.snapshot.fetched' => $this->weatherSnapshotFetchedHandler,
            default => throw new InvalidArgumentException("Nepoznat event za routing: {$eventName}"),
        };
    }
}
