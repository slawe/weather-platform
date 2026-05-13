package contracts

import (
	"context"

	"ingestion-weatherapi/internal/domain/weather"
)

/*
LocationRepository predstavlja application contract za pristup aktivnim lokacijama.

Application sloj ne treba da zna da li lokacije dolaze iz Postgresa,
nekog fajla, cache-a ili nečeg trećeg.
*/
type LocationRepository interface {
	/*
		GetActiveLocations vraća sve aktivne lokacije koje treba uključiti u ingestion proces.
	*/
	GetActiveLocations(ctx context.Context) ([]weather.Location, error)
}
