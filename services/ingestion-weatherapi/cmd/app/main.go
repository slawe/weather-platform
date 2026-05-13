package main

import (
	"context"
	"fmt"
	"log"

	outboxservices "ingestion-weatherapi/internal/application/outbox/services"
	weatheractions "ingestion-weatherapi/internal/application/weather/actions"
	"ingestion-weatherapi/internal/config"
	"ingestion-weatherapi/internal/infrastructure/persistence/postgres"
	"ingestion-weatherapi/internal/infrastructure/sources/weatherapi"
)

/*
Main je za sada razvojni entry point.

Ovim proveravamo ceo application flow sa realnom bazom:
- izvrši migracije
- izvrši seed lokacija
- učitaj lokacije iz Postgresa
- pozovi WeatherAPI source
- napravi domain event preko agregata
- prevedi domain event u outbox poruku
- sačuvaj outbox poruke u Postgres
*/
func main() {
	ctx := context.Background()

	cfg, err := config.Load()
	if err != nil {
		log.Fatalf("greška pri učitavanju konfiguracije: %v", err)
	}

	dbPool, err := postgres.NewConnectionPool(ctx, cfg)
	if err != nil {
		log.Fatalf("greška pri povezivanju na Postgres: %v", err)
	}
	defer dbPool.Close()

	weatherAPIClient := weatherapi.NewClient(cfg)
	weatherAPIMapper := weatherapi.NewMapper(cfg)
	weatherDataSource := weatherapi.NewDataSource(weatherAPIClient, weatherAPIMapper)

	locationRepository := postgres.NewLocationRepository(dbPool)
	outboxRepository := postgres.NewOutboxRepository(dbPool)
	outboxMessageFactory := outboxservices.NewOutboxMessageFactory(cfg.App.ServiceName)

	action := weatheractions.NewFetchWeatherForLocationsAction(
		locationRepository,
		weatherDataSource,
		outboxMessageFactory,
		outboxRepository,
	)

	createdMessages, err := action.Execute(ctx)
	if err != nil {
		log.Fatalf("greška pri izvršavanju ingestion flow-a: %v", err)
	}

	fmt.Printf("Ingestion flow završen. Kreirano outbox poruka: %d\n", createdMessages)
}
