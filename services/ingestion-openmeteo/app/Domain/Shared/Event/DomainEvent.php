<?php

namespace App\Domain\Shared\Event;

/**
 * Osnovni contract za sve domain evente u sistemu.
 *
 * Domain event predstavlja nešto što se već desilo u domenu i što želimo
 * dalje da zabeležimo, obradimo ili pretvorimo u integration event.
 */
interface DomainEvent
{
    /**
     * Jedinstveni identifikator eventa.
     */
    public function eventId(): string;

    /**
     * Naziv eventa unutar domena.
     */
    public function eventName(): string;

    /**
     * Verzija event contract-a.
     */
    public function eventVersion(): int;

    /**
     * Vreme kada se događaj desio.
     */
    public function occurredAt(): string;

    /**
     * Podaci eventa koji će kasnije biti korišćeni za outbox/integration poruku.
     */
    public function payload(): array;
}
