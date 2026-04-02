<?php

namespace App\Domain\Shared\Event;

/**
 * Trait koji omogućava entitetima da beleže domain evente.
 *
 * Ideja je da entitet unutar svoje biznis logike evidentira šta se desilo,
 * a da application sloj kasnije te evente preuzme i odluči šta će sa njima:
 * upis u outbox, slanje na broker, audit log i slično.
 */
trait RecordsEvents
{
    /**
     * @var DomainEvent[]
     */
    private array $recordedEvents = [];

    /**
     * Dodaje novi domain event u internu listu.
     */
    protected function recordEvent(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * Vraća sve do sada zabeležene evente bez brisanja.
     *
     * @return DomainEvent[]
     */
    public function recordedEvents(): array
    {
        return $this->recordedEvents;
    }

    /**
     * Vraća sve evente i prazni internu listu.
     *
     * Ovo je korisno kada application sloj želi da preuzme evente
     * i osigura da se isti eventi ne obrađuju ponovo u okviru iste instance.
     *
     * @return DomainEvent[]
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
