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
	upsertQuery := `
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

	activeLocationIDs := make([]string, 0, len(cfg.WeatherAPI.DefaultLocations))

	for _, location := range cfg.WeatherAPI.DefaultLocations {
		if _, err := pool.Exec(
			ctx,
			upsertQuery,
			location.ID,
			location.Name,
			location.Country,
			location.Latitude,
			location.Longitude,
		); err != nil {
			return fmt.Errorf("neuspešan seed lokacije %s: %w", location.Name, err)
		}

		activeLocationIDs = append(activeLocationIDs, location.ID)
	}

	// Lokacije koje više nisu u konfiguraciji gasimo da scheduler ne šalje zastarele podatke.
	if len(activeLocationIDs) > 0 {
		if _, err := pool.Exec(
			ctx,
			`UPDATE locations SET is_active = FALSE, updated_at = NOW() WHERE id <> ALL($1::uuid[])`,
			activeLocationIDs,
		); err != nil {
			return fmt.Errorf("neuspešno deaktiviranje zastarelih lokacija: %w", err)
		}
	}

	return nil
}
