package contracts

import (
	"context"

	outboxdto "ingestion-weatherapi/internal/application/outbox/dto"
)

/*
OutboxRepository predstavlja application contract za rad sa outbox porukama.
*/
type OutboxRepository interface {
	/*
		StoreMany čuva jednu ili više outbox poruka.
	*/
	StoreMany(ctx context.Context, messages []outboxdto.OutboxMessageData) error

	/*
		LockPendingBatch zaključava batch pending poruka za publish.

		FOR UPDATE SKIP LOCKED nam omogućava da više publisher procesa
		ne uzmu iste poruke u isto vreme.
	*/
	LockPendingBatch(ctx context.Context, limit int) ([]outboxdto.OutboxMessageData, error)

	/*
		MarkAsPublished označava poruku kao uspešno objavljenu.
	*/
	MarkAsPublished(ctx context.Context, id int64) error

	/*
		MarkAsFailed označava poruku kao failed ili je vraća u pending sa backoff-om.
	*/
	MarkAsFailed(ctx context.Context, id int64, errorMessage string, attempts int, maxAttempts int) error
}
