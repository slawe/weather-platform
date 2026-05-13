<?php

namespace App\Application\Messaging\Services;

use App\Application\Messaging\Contracts\IdempotencyRepository;
use App\Application\Messaging\Contracts\MessageHandler;
use App\Application\Messaging\DTO\IncomingMessage;
use Illuminate\Support\Facades\DB;

/**
 * Servis koji obezbeđuje da se isti event ne obradi više puta.
 *
 * Ovo je važan deo event-driven sistema, jer broker može isporučiti
 * istu poruku više puta, a mi želimo da obrada bude bez duplih efekata.
 */
class IdempotencyService
{
    /**
     * IdempotencyService konstruktor.
     *
     * @param IdempotencyRepository $idempotencyRepository
     */
    public function __construct(private IdempotencyRepository $idempotencyRepository) {}

    public function handleOnce(IncomingMessage $message, MessageHandler $handler): bool
    {
        return DB::transaction(function () use ($message, $handler): bool {
            DB::statement('LOCK TABLE consumed_events IN SHARE ROW EXCLUSIVE MODE');

            if ($this->idempotencyRepository->alreadyProcessed($message->eventId())) {
                return false;
            }

            $handler->handle($message);

            $this->idempotencyRepository->markProcessed(
                $message->metadata->eventId,
                $message->metadata->eventName,
            );

            return true;
        });
    }
}
