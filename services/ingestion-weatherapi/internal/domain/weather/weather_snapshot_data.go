package weather

/*
WeatherSnapshotData predstavlja naš interni, normalizovani model vremenskog snapshot-a.

Ovo je canonical domain model unutar servisa:
- source može biti WeatherAPI danas
- neki drugi API sutra
- ali ostatak sistema radi sa istim oblikom podataka.
*/
type WeatherSnapshotData struct {
	LocationID   string
	City         string
	Country      string
	Latitude     float64
	Longitude    float64
	TemperatureC float64
	WindSpeedKmh float64
	WeatherCode  int
	ObservedAt   string
	Source       string
}

/*
ToMap vraća snapshot kao mapu pogodnu za event payload ili debug ispis.
*/
func (d WeatherSnapshotData) ToMap() map[string]any {
	return map[string]any{
		"location_id":    d.LocationID,
		"city":           d.City,
		"country":        d.Country,
		"latitude":       d.Latitude,
		"longitude":      d.Longitude,
		"temperature_c":  d.TemperatureC,
		"wind_speed_kmh": d.WindSpeedKmh,
		"weather_code":   d.WeatherCode,
		"observed_at":    d.ObservedAt,
		"source":         d.Source,
	}
}
