package postgres

import (
	"context"
	"fmt"

	"github.com/jackc/pgx/v5/pgxpool"
)

/*
RunMigrations izvršava osnovne migracije za ingestion-weatherapi servis.

Za v1 namerno držimo migracije jednostavno u Go kodu.
Kasnije možemo preći na goose/migrate alat ako projekat poraste.
*/
func RunMigrations(ctx context.Context, pool *pgxpool.Pool) error {
	migrations := []string{
		createLocationsTableSQL,
		createOutboxMessagesTableSQL,
	}

	for _, migration := range migrations {
		if _, err := pool.Exec(ctx, migration); err != nil {
			return fmt.Errorf("neuspešno izvršavanje migracije: %w", err)
		}
	}

	return nil
}

const createLocationsTableSQL = `
CREATE TABLE IF NOT EXISTS locations (
    id UUID PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    country VARCHAR(120) NOT NULL,
    latitude DOUBLE PRECISION NOT NULL,
    longitude DOUBLE PRECISION NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_locations_is_active
    ON locations (is_active);
`

const createOutboxMessagesTableSQL = `
CREATE TABLE IF NOT EXISTS outbox_messages (
    id BIGSERIAL PRIMARY KEY,
    event_id UUID NOT NULL UNIQUE,
    event_name VARCHAR(150) NOT NULL,
    event_version INTEGER NOT NULL,
    routing_key VARCHAR(150) NOT NULL,
    deduplication_key VARCHAR(255) NOT NULL UNIQUE,
    payload JSONB NOT NULL,
    headers JSONB NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending',
    attempts INTEGER NOT NULL DEFAULT 0,
    available_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    published_at TIMESTAMPTZ NULL,
    last_error TEXT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_outbox_messages_status_available_at
    ON outbox_messages (status, available_at);

CREATE INDEX IF NOT EXISTS idx_outbox_messages_event_name
    ON outbox_messages (event_name);
`
