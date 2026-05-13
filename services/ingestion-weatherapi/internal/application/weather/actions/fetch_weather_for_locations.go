package actions

import (
	"context"

	outboxcontracts "ingestion-weatherapi/internal/application/outbox/contracts"
	outboxdto "ingestion-weatherapi/internal/application/outbox/dto"
	outboxservices "ingestion-weatherapi/internal/application/outbox/services"
	weathercontracts "ingestion-weatherapi/internal/application/weather/contracts"
	"ingestion-weatherapi/internal/domain/weather"
)

/*
FetchWeatherForLocationsAction predstavlja glavni use case ingestion servisa.

Njegov posao je da:
- učita aktivne lokacije
- za svaku lokaciju pozove source
- kroz agregat kreira domain event
- domain evente prevede u outbox poruke
- sačuva outbox poruke
*/
type FetchWeatherForLocationsAction struct {
	locationRepository   weathercontracts.LocationRepository
	weatherDataSource    weathercontracts.WeatherDataSource
	outboxMessageFactory *outboxservices.OutboxMessageFactory
	outboxRepository     outboxcontracts.OutboxRepository
}

/*
NewFetchWeatherForLocationsAction pravi novu instancu use case-a.
*/
func NewFetchWeatherForLocationsAction(
	locationRepository weathercontracts.LocationRepository,
	weatherDataSource weathercontracts.WeatherDataSource,
	outboxMessageFactory *outboxservices.OutboxMessageFactory,
	outboxRepository outboxcontracts.OutboxRepository,
) *FetchWeatherForLocationsAction {
	return &FetchWeatherForLocationsAction{
		locationRepository:   locationRepository,
		weatherDataSource:    weatherDataSource,
		outboxMessageFactory: outboxMessageFactory,
		outboxRepository:     outboxRepository,
	}
}

/*
Execute pokreće ingestion za sve aktivne lokacije.

Vraća broj kreiranih outbox poruka.
*/
func (a *FetchWeatherForLocationsAction) Execute(ctx context.Context) (int, error) {
	locations, err := a.locationRepository.GetActiveLocations(ctx)
	if err != nil {
		return 0, err
	}

	if len(locations) == 0 {
		return 0, nil
	}

	collectedMessages := make([]outboxdto.OutboxMessageData, 0)

	for _, location := range locations {
		snapshot, err := a.weatherDataSource.FetchCurrentWeather(ctx, location)
		if err != nil {
			return 0, err
		}

		ingestion := weather.NewWeatherIngestion(location)
		ingestion.RecordFetchedSnapshot(snapshot)

		events := ingestion.ReleaseEvents()
		messages := a.outboxMessageFactory.FromDomainEvents(events)

		collectedMessages = append(collectedMessages, messages...)
	}

	if len(collectedMessages) == 0 {
		return 0, nil
	}

	if err := a.outboxRepository.StoreMany(ctx, collectedMessages); err != nil {
		return 0, err
	}

	return len(collectedMessages), nil
}
