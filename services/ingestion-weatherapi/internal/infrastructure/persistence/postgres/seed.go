package postgres

import (
	"context"
	"fmt"

	"github.com/jackc/pgx/v5/pgxpool"

	"ingestion-weatherapi/internal/config"
)

/*
SeedLocations ubacuje početne lokacije za lokalni razvoj.

Koristimo ON CONFLICT da seed bude idempotentan:
možeš ga pokrenuti više puta bez dupliranja podataka.
*/
func SeedLocations(ctx context.Context, pool *pgxpool.Pool, cfg *config.Config) error {
	query := `
INSERT INTO locations (
    id,
    name,
    country,
    latitude,
    longitude,
    is_active
) VALUES (
    $1,
    $2,
    $3,
    $4,
    $5,
    TRUE
)
ON CONFLICT (id) DO UPDATE SET
    name = EXCLUDED.name,
    country = EXCLUDED.country,
    latitude = EXCLUDED.latitude,
    longitude = EXCLUDED.longitude,
    is_active = EXCLUDED.is_active,
    updated_at = NOW();
`

	for _, location := range cfg.WeatherAPI.DefaultLocations {
		if _, err := pool.Exec(
			ctx,
			query,
			location.ID,
			location.Name,
			location.Country,
			location.Latitude,
			location.Longitude,
		); err != nil {
			return fmt.Errorf("neuspešan seed lokacije %s: %w", location.Name, err)
		}
	}

	return nil
}
