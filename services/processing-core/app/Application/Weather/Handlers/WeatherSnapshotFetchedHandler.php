<?php

namespace App\Application\Weather\Handlers;

use App\Application\Messaging\Contracts\MessageHandler;
use App\Application\Messaging\DTO\IncomingMessage;
use App\Application\Weather\Contracts\WeatherSnapshotRepository;
use App\Application\Weather\DTO\WeatherSnapshotPayload;

/**
 * Handler za event weather.snapshot.fetched.
 *
 * Njegova odgovornost je da incoming poruku prevede u tipizovani payload
 * i upiše rezultat u lokalni read model.
 */
final readonly class WeatherSnapshotFetchedHandler implements MessageHandler
{
    /**
     * WeatherSnapshotFetchedHandler konstruktor.
     *
     * @param WeatherSnapshotRepository $weatherSnapshotRepository
     */
    public function __construct(private WeatherSnapshotRepository $weatherSnapshotRepository) {}

    /**
     * Obrada incoming poruke.
     *
     * 1. Prevede payload u WeatherSnapshotPayload.
     * 2. Sprema u lokalni read model.
     *
     * @param IncomingMessage $message
     * @return void
     */
    public function handle(IncomingMessage $message): void
    {
        $payload = WeatherSnapshotPayload::fromArray($message->payload());
        $this->weatherSnapshotRepository->store($message->metadata, $payload);
    }
}
