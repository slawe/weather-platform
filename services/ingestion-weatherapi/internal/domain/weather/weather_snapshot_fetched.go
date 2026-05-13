package weather

import (
	"time"

	"github.com/google/uuid"
)

/*
WeatherSnapshotFetched predstavlja domain event koji nastaje kada servis
uspešno preuzme i normalizuje vremenski snapshot za datu lokaciju.
*/
type WeatherSnapshotFetched struct {
	id         string
	occurredAt string
	location   Location
	snapshot   WeatherSnapshotData
}

/*
NewWeatherSnapshotFetched pravi novi domain event za uspešno preuzet snapshot.
*/
func NewWeatherSnapshotFetched(
	location Location,
	snapshot WeatherSnapshotData,
) *WeatherSnapshotFetched {
	return &WeatherSnapshotFetched{
		id:         uuid.NewString(),
		occurredAt: time.Now().UTC().Format(time.RFC3339),
		location:   location,
		snapshot:   snapshot,
	}
}

/*
EventID vraća jedinstveni identifikator eventa.
*/
func (e *WeatherSnapshotFetched) EventID() string {
	return e.id
}

/*
EventName vraća canonical naziv domain eventa.
*/
func (e *WeatherSnapshotFetched) EventName() string {
	return "weather.snapshot.fetched"
}

/*
EventVersion vraća verziju event contract-a.
*/
func (e *WeatherSnapshotFetched) EventVersion() int {
	return 1
}

/*
OccurredAt vraća vreme nastanka eventa.
*/
func (e *WeatherSnapshotFetched) OccurredAt() string {
	return e.occurredAt
}

/*
Payload vraća canonical payload domen događaja.
*/
func (e *WeatherSnapshotFetched) Payload() map[string]any {
	return e.snapshot.ToMap()
}

/*
Location vraća lokaciju kojoj događaj pripada.
*/
func (e *WeatherSnapshotFetched) Location() Location {
	return e.location
}
