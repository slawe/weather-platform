package postgres

import (
	"context"
	"encoding/json"
	"fmt"
	"strings"

	outboxdto "ingestion-weatherapi/internal/application/outbox/dto"

	"github.com/jackc/pgx/v5/pgxpool"
)

/*
OutboxRepository je Postgres implementacija application OutboxRepository contract-a.

Njegov posao je da domain/application event envelope sačuva u outbox tabelu,
a publisher command kasnije čita pending poruke i šalje ih u RabbitMQ.
*/
type OutboxRepository struct {
	pool *pgxpool.Pool
}

/*
NewOutboxRepository pravi novu Postgres outbox repository instancu.
*/
func NewOutboxRepository(pool *pgxpool.Pool) *OutboxRepository {
	return &OutboxRepository{
		pool: pool,
	}
}

/*
StoreMany čuva više outbox poruka u jednoj transakciji.
*/
func (r *OutboxRepository) StoreMany(
	ctx context.Context,
	messages []outboxdto.OutboxMessageData,
) error {
	if len(messages) == 0 {
		return nil
	}

	tx, err := r.pool.Begin(ctx)
	if err != nil {
		return fmt.Errorf("neuspešno otvaranje transakcije za outbox: %w", err)
	}
	defer tx.Rollback(ctx)

	query := `
INSERT INTO outbox_messages (
    event_id,
    event_name,
    event_version,
    routing_key,
    payload,
    headers,
    status,
    attempts,
    available_at,
    published_at,
    last_error
) VALUES (
    $1,
    $2,
    $3,
    $4,
    $5,
    $6,
    $7,
    $8,
    $9::timestamptz,
    $10,
    $11
)
ON CONFLICT (event_id) DO NOTHING;
`

	for _, message := range messages {
		payloadJSON, err := json.Marshal(message.Payload)
		if err != nil {
			return fmt.Errorf("neuspešno JSON enkodovanje outbox payload-a: %w", err)
		}

		headersJSON, err := json.Marshal(message.Headers)
		if err != nil {
			return fmt.Errorf("neuspešno JSON enkodovanje outbox headers-a: %w", err)
		}

		if _, err := tx.Exec(
			ctx,
			query,
			message.EventID,
			message.EventName,
			message.EventVersion,
			message.RoutingKey,
			payloadJSON,
			headersJSON,
			message.Status,
			message.Attempts,
			message.AvailableAt,
			message.PublishedAt,
			message.LastError,
		); err != nil {
			return fmt.Errorf("neuspešan upis outbox poruke %s: %w", message.EventID, err)
		}
	}

	if err := tx.Commit(ctx); err != nil {
		return fmt.Errorf("neuspešan commit outbox transakcije: %w", err)
	}

	return nil
}

/*
LockPendingBatch zaključava pending poruke i povećava broj pokušaja.

Koristimo FOR UPDATE SKIP LOCKED da izbegnemo da dva publisher procesa uzmu iste redove.
*/
func (r *OutboxRepository) LockPendingBatch(
	ctx context.Context,
	limit int,
) ([]outboxdto.OutboxMessageData, error) {
	tx, err := r.pool.Begin(ctx)
	if err != nil {
		return nil, fmt.Errorf("neuspešno otvaranje transakcije za lock outbox batch-a: %w", err)
	}
	defer tx.Rollback(ctx)

	rows, err := tx.Query(ctx, `
SELECT
    id,
    event_id::text,
    event_name,
    event_version,
    routing_key,
    payload,
    headers,
    status,
    attempts,
    available_at::text,
    published_at::text,
    last_error
FROM outbox_messages
WHERE status = 'pending'
  AND available_at <= NOW()
ORDER BY id ASC
LIMIT $1
FOR UPDATE SKIP LOCKED;
`, limit)
	if err != nil {
		return nil, fmt.Errorf("neuspešno zaključavanje pending outbox poruka: %w", err)
	}
	defer rows.Close()

	messages := make([]outboxdto.OutboxMessageData, 0)
	ids := make([]int64, 0)

	for rows.Next() {
		var message outboxdto.OutboxMessageData
		var payloadJSON []byte
		var headersJSON []byte

		if err := rows.Scan(
			&message.ID,
			&message.EventID,
			&message.EventName,
			&message.EventVersion,
			&message.RoutingKey,
			&payloadJSON,
			&headersJSON,
			&message.Status,
			&message.Attempts,
			&message.AvailableAt,
			&message.PublishedAt,
			&message.LastError,
		); err != nil {
			return nil, fmt.Errorf("neuspešno mapiranje outbox poruke: %w", err)
		}

		if err := json.Unmarshal(payloadJSON, &message.Payload); err != nil {
			return nil, fmt.Errorf("neuspešno JSON parsiranje outbox payload-a: %w", err)
		}

		if err := json.Unmarshal(headersJSON, &message.Headers); err != nil {
			return nil, fmt.Errorf("neuspešno JSON parsiranje outbox headers-a: %w", err)
		}

		message.Attempts++

		messages = append(messages, message)
		ids = append(ids, message.ID)
	}

	if err := rows.Err(); err != nil {
		return nil, fmt.Errorf("greška tokom iteracije kroz outbox poruke: %w", err)
	}

	if len(ids) > 0 {
		placeholders := make([]string, 0, len(ids))
		args := make([]any, 0, len(ids))

		for index, id := range ids {
			placeholders = append(placeholders, fmt.Sprintf("$%d", index+1))
			args = append(args, id)
		}

		query := fmt.Sprintf(`
UPDATE outbox_messages
SET attempts = attempts + 1,
    updated_at = NOW()
WHERE id IN (%s);
`, strings.Join(placeholders, ","))

		if _, err := tx.Exec(ctx, query, args...); err != nil {
			return nil, fmt.Errorf("neuspešno prebacivanje outbox poruka u processing: %w", err)
		}
	}

	if err := tx.Commit(ctx); err != nil {
		return nil, fmt.Errorf("neuspešan commit lock outbox batch-a: %w", err)
	}

	return messages, nil
}

/*
MarkAsPublished označava poruku kao uspešno objavljenu.
*/
func (r *OutboxRepository) MarkAsPublished(ctx context.Context, id int64) error {
	_, err := r.pool.Exec(ctx, `
UPDATE outbox_messages
SET status = 'published',
    published_at = NOW(),
    updated_at = NOW(),
    last_error = NULL
WHERE id = $1;
`, id)

	if err != nil {
		return fmt.Errorf("neuspešno označavanje outbox poruke kao published: %w", err)
	}

	return nil
}

/*
MarkAsFailed označava poruku kao failed ili je vraća u pending sa malim backoff-om.
*/
func (r *OutboxRepository) MarkAsFailed(
	ctx context.Context,
	id int64,
	errorMessage string,
	attempts int,
	maxAttempts int,
) error {
	status := "pending"

	if attempts >= maxAttempts {
		status = "failed"
	}

	_, err := r.pool.Exec(ctx, `
UPDATE outbox_messages
SET status = $1,
    available_at = CASE
        WHEN $1 = 'pending' THEN NOW() + INTERVAL '10 seconds'
        ELSE available_at
    END,
    last_error = $2,
    updated_at = NOW()
WHERE id = $3;
`, status, errorMessage, id)

	if err != nil {
		return fmt.Errorf("neuspešno označavanje outbox poruke kao failed/pending: %w", err)
	}

	return nil
}
