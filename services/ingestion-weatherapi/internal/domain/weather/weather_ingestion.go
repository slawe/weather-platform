package weather

import (
	sharedEvent "ingestion-weatherapi/internal/domain/shared/event"
)

/*
WeatherIngestion predstavlja agregat procesa ingestije vremenskih podataka
za jednu lokaciju.
*/
type WeatherIngestion struct {
	location Location
	recorder sharedEvent.Recorder
}

/*
NewWeatherIngestion pravi novi agregat za zadatu lokaciju.
*/
func NewWeatherIngestion(location Location) *WeatherIngestion {
	return &WeatherIngestion{
		location: location,
	}
}

/*
RecordFetchedSnapshot beleži domain event da je snapshot uspešno preuzet
i normalizovan za lokaciju.
*/
func (w *WeatherIngestion) RecordFetchedSnapshot(snapshot WeatherSnapshotData) {
	w.recorder.Record(
		NewWeatherSnapshotFetched(w.location, snapshot),
	)
}

/*
ReleaseEvents vraća sve zabeležene domain evente iz agregata.
*/
func (w *WeatherIngestion) ReleaseEvents() []sharedEvent.DomainEvent {
	return w.recorder.Release()
}

/*
Location vraća lokaciju za koju agregat radi ingestiju.
*/
func (w *WeatherIngestion) Location() Location {
	return w.location
}
