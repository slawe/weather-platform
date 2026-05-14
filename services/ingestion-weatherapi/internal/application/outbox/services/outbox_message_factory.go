package services

import (
	"time"

	outboxdto "ingestion-weatherapi/internal/application/outbox/dto"
	sharedEvent "ingestion-weatherapi/internal/domain/shared/event"
)

/*
OutboxMessageFactory prevodi domain evente u canonical outbox poruke.

Time jasno razdvajamo:
- domain event koji nastaje u agregatu
- outbox zapis koji je spreman za persistence i kasniji publish
*/
type OutboxMessageFactory struct {
	serviceName string
}

/*
NewOutboxMessageFactory pravi novu instancu factory-ja.
*/
func NewOutboxMessageFactory(serviceName string) *OutboxMessageFactory {
	return &OutboxMessageFactory{
		serviceName: serviceName,
	}
}

/*
FromDomainEvent prevodi jedan domain event u jednu outbox poruku.
*/
func (f *OutboxMessageFactory) FromDomainEvent(event sharedEvent.DomainEvent) outboxdto.OutboxMessageData {
	return outboxdto.OutboxMessageData{
		EventID:          event.EventID(),
		EventName:        event.EventName(),
		EventVersion:     event.EventVersion(),
		RoutingKey:       event.EventName(),
		DeduplicationKey: f.deduplicationKey(event),
		Payload: map[string]any{
			"event_id":      event.EventID(),
			"event_name":    event.EventName(),
			"event_version": event.EventVersion(),
			"occurred_at":   event.OccurredAt(),
			"producer":      f.serviceName,
			"payload":       event.Payload(),
		},
		Headers: map[string]any{
			"x-event-id":      event.EventID(),
			"x-event-name":    event.EventName(),
			"x-event-version": event.EventVersion(),
			"x-producer":      f.serviceName,
		},
		Status:      "pending",
		Attempts:    0,
		AvailableAt: time.Now().UTC().Format(time.RFC3339),
		PublishedAt: nil,
		LastError:   nil,
	}
}

/*
deduplicationKey kreira stabilan key za latest-state outbox zapis.
*/
func (f *OutboxMessageFactory) deduplicationKey(event sharedEvent.DomainEvent) string {
	payload := event.Payload()

	locationID, _ := payload["location_id"].(string)
	source, _ := payload["source"].(string)

	return event.EventName() + ":" + locationID + ":" + source
}

/*
FromDomainEvents prevodi više domain eventa u outbox poruke.
*/
func (f *OutboxMessageFactory) FromDomainEvents(events []sharedEvent.DomainEvent) []outboxdto.OutboxMessageData {
	messages := make([]outboxdto.OutboxMessageData, 0, len(events))

	for _, event := range events {
		messages = append(messages, f.FromDomainEvent(event))
	}

	return messages
}
