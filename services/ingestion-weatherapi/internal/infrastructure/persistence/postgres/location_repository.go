package postgres

import (
	"context"
	"fmt"

	"github.com/jackc/pgx/v5/pgxpool"

	"ingestion-weatherapi/internal/domain/weather"
)

/*
LocationRepository je Postgres implementacija application LocationRepository contract-a.

Application sloj ne zna da koristimo Postgres.
On samo traži aktivne lokacije.
*/
type LocationRepository struct {
	pool *pgxpool.Pool
}

/*
NewLocationRepository pravi novu Postgres repository instancu.
*/
func NewLocationRepository(pool *pgxpool.Pool) *LocationRepository {
	return &LocationRepository{
		pool: pool,
	}
}

/*
GetActiveLocations vraća sve aktivne lokacije iz baze.

Lokacije su prethodno ubačene kroz seed.
*/
func (r *LocationRepository) GetActiveLocations(ctx context.Context) ([]weather.Location, error) {
	rows, err := r.pool.Query(ctx, `
SELECT
    id::text,
    name,
    country,
    latitude,
    longitude,
    is_active
FROM locations
WHERE is_active = TRUE
ORDER BY name ASC;
`)
	if err != nil {
		return nil, fmt.Errorf("neuspešno učitavanje aktivnih lokacija: %w", err)
	}
	defer rows.Close()

	locations := make([]weather.Location, 0)

	for rows.Next() {
		var location weather.Location

		if err := rows.Scan(
			&location.ID,
			&location.Name,
			&location.Country,
			&location.Latitude,
			&location.Longitude,
			&location.IsActive,
		); err != nil {
			return nil, fmt.Errorf("neuspešno mapiranje lokacije iz baze: %w", err)
		}

		locations = append(locations, location)
	}

	if err := rows.Err(); err != nil {
		return nil, fmt.Errorf("greška tokom iteracije kroz lokacije: %w", err)
	}

	return locations, nil
}
