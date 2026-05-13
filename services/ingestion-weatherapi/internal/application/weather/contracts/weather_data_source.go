package contracts

import (
	"context"

	"ingestion-weatherapi/internal/domain/weather"
)

/*
WeatherDataSource predstavlja application contract za izvor vremenskih podataka.

Application sloj ne treba da zna da li podatke dobija iz WeatherAPI,
Open-Meteo, nekog trećeg HTTP API-ja ili mock implementacije.
*/
type WeatherDataSource interface {
	/*
		FetchCurrentWeather dohvaća trenutni vremenski snapshot za zadatu lokaciju.
	*/
	FetchCurrentWeather(ctx context.Context, location weather.Location) (weather.WeatherSnapshotData, error)
}
