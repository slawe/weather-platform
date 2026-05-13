package weatherapi

import (
	"encoding/json"
	"fmt"

	"ingestion-weatherapi/internal/config"
	"ingestion-weatherapi/internal/domain/weather"
)

/*
Mapper prevodi raw WeatherAPI response u naš interni canonical domain model.

Ovde izolujemo znanje o spoljnjem API shape-u.
Ako se WeatherAPI promeni, menjamo ovaj deo, a ne ostatak sistema.
*/
type Mapper struct {
	config *config.Config
}

/*
NewMapper pravi novu instancu mapper-a.
*/
func NewMapper(cfg *config.Config) *Mapper {
	return &Mapper{
		config: cfg,
	}
}

/*
MapCurrentWeather prevodi raw JSON odgovor u WeatherSnapshotData.
*/
func (m *Mapper) MapCurrentWeather(
	body []byte,
	location weather.Location,
) (*weather.WeatherSnapshotData, error) {
	var response CurrentWeatherResponse

	if err := json.Unmarshal(body, &response); err != nil {
		return nil, fmt.Errorf("neuspešno parsiranje WeatherAPI response-a: %w", err)
	}

	return &weather.WeatherSnapshotData{
		LocationID:   location.ID,
		City:         response.Location.Name,
		Country:      response.Location.Country,
		Latitude:     response.Location.Latitude,
		Longitude:    response.Location.Longitude,
		TemperatureC: response.Current.TempC,
		WindSpeedKmh: response.Current.WindKph,
		WeatherCode:  response.Current.Condition.Code,
		ObservedAt:   response.Current.LastUpdated,
		Source:       m.config.App.SourceName,
	}, nil
}
