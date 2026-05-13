package dto

/*
OutboxMessageData predstavlja canonical podatke jedne outbox poruke.

Isti DTO koristimo za:
- upis nove outbox poruke
- čitanje pending poruka za publish
*/
type OutboxMessageData struct {
	ID           int64
	EventID      string
	EventName    string
	EventVersion int
	RoutingKey   string
	Payload      map[string]any
	Headers      map[string]any
	Status       string
	Attempts     int
	AvailableAt  string
	PublishedAt  *string
	LastError    *string
}
