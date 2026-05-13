package weatherapi

import (
	"context"
	"fmt"

	"ingestion-weatherapi/internal/domain/weather"
)

/*
DataSource implementira application WeatherDataSource contract koristeći WeatherAPI.

Ovo je adapter između application sloja i spoljnog HTTP source-a.
*/
type DataSource struct {
	client *Client
	mapper *Mapper
}

/*
NewDataSource pravi novu instancu WeatherAPI data source adaptera.
*/
func NewDataSource(client *Client, mapper *Mapper) *DataSource {
	return &DataSource{
		client: client,
		mapper: mapper,
	}
}

/*
FetchCurrentWeather dohvaća trenutni vremenski snapshot za zadatu lokaciju.

Application sloj vidi samo ovaj contract, ne zna da iza postoji HTTP API.
*/
func (s *DataSource) FetchCurrentWeather(
	ctx context.Context,
	location weather.Location,
) (weather.WeatherSnapshotData, error) {
	query := location.Name

	if query == "" {
		return weather.WeatherSnapshotData{}, fmt.Errorf("lokacija nema naziv za WeatherAPI query")
	}

	body, err := s.client.FetchCurrentRaw(ctx, query)
	if err != nil {
		return weather.WeatherSnapshotData{}, err
	}

	snapshot, err := s.mapper.MapCurrentWeather(body, location)
	if err != nil {
		return weather.WeatherSnapshotData{}, err
	}

	return *snapshot, nil
}
