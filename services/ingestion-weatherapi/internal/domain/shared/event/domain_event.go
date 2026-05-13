package event

/*
DomainEvent predstavlja osnovni contract za sve domain evente u servisu.

Ovo je zajednička apstrakcija koju application sloj kasnije može da koristi
bez znanja o konkretnom event tipu.
*/
type DomainEvent interface {
	EventID() string
	EventName() string
	EventVersion() int
	OccurredAt() string
	Payload() map[string]any
}
