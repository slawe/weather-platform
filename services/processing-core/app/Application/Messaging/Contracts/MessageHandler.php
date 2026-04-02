<?php

namespace App\Application\Messaging\Contracts;

use App\Application\Messaging\DTO\IncomingMessage;

/**
 * Opšti contract za obradu incoming poruka.
 *
 * Svaki konkretan handler zna da obradi određeni event type.
 */
interface MessageHandler
{
    /**
     * Obrađuje incoming poruku.
     */
    public function handle(IncomingMessage $message): void;
}
