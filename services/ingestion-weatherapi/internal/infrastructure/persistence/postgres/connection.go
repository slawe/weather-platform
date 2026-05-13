package postgres

import (
	"context"
	"fmt"

	"github.com/jackc/pgx/v5/pgxpool"

	"ingestion-weatherapi/internal/config"
)

/*
NewConnectionPool pravi Postgres connection pool.

Koristimo pgxpool jer je idiomatski i stabilan izbor za Go + Postgres.
*/
func NewConnectionPool(ctx context.Context, cfg *config.Config) (*pgxpool.Pool, error) {
	dsn := fmt.Sprintf(
		"postgres://%s:%s@%s:%d/%s?sslmode=disable",
		cfg.Database.User,
		cfg.Database.Password,
		cfg.Database.Host,
		cfg.Database.Port,
		cfg.Database.Name,
	)

	pool, err := pgxpool.New(ctx, dsn)
	if err != nil {
		return nil, fmt.Errorf("neuspešno kreiranje Postgres pool-a: %w", err)
	}

	if err := pool.Ping(ctx); err != nil {
		pool.Close()
		return nil, fmt.Errorf("neuspešan ping ka Postgres bazi: %w", err)
	}

	return pool, nil
}
