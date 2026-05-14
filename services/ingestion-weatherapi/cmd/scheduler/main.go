package main

import (
	"context"
	"log"
	"os/signal"
	"syscall"
	"time"

	outboxservices "ingestion-weatherapi/internal/application/outbox/services"
	weatheractions "ingestion-weatherapi/internal/application/weather/actions"
	"ingestion-weatherapi/internal/config"
	"ingestion-weatherapi/internal/infrastructure/messaging/rabbitmq"
	"ingestion-weatherapi/internal/infrastructure/persistence/postgres"
	"ingestion-weatherapi/internal/infrastructure/sources/weatherapi"

	"github.com/jackc/pgx/v5/pgxpool"
)

const maxOutboxAttempts = 5

/*
Scheduler je long-running proces koji periodično pokreće WeatherAPI fetch
i outbox publish, slično Laravel scheduler-u u ingestion-openmeteo servisu.
*/
func main() {
	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	cfg, err := config.Load()
	if err != nil {
		log.Fatalf("greška pri učitavanju konfiguracije: %v", err)
	}

	dbPool, err := postgres.NewConnectionPool(ctx, cfg)
	if err != nil {
		log.Fatalf("greška pri povezivanju na Postgres: %v", err)
	}
	defer dbPool.Close()

	fetchAction := buildFetchAction(cfg, dbPool)
	outboxRepository := postgres.NewOutboxRepository(dbPool)
	publisher := rabbitmq.NewPublisher(cfg)

	fetchInterval := time.Duration(cfg.Scheduler.FetchIntervalSeconds) * time.Second
	publishInterval := time.Duration(cfg.Scheduler.OutboxPublishIntervalSeconds) * time.Second

	log.Printf(
		"ingestion-weatherapi scheduler pokrenut. fetch_interval=%s publish_interval=%s",
		fetchInterval,
		publishInterval,
	)

	runFetch(ctx, fetchAction)
	runPublish(ctx, outboxRepository, publisher)

	fetchTicker := time.NewTicker(fetchInterval)
	defer fetchTicker.Stop()

	publishTicker := time.NewTicker(publishInterval)
	defer publishTicker.Stop()

	for {
		select {
		case <-ctx.Done():
			log.Println("ingestion-weatherapi scheduler se gasi")
			return
		case <-fetchTicker.C:
			runFetch(ctx, fetchAction)
		case <-publishTicker.C:
			runPublish(ctx, outboxRepository, publisher)
		}
	}
}

/*
buildFetchAction sklapa application use case za WeatherAPI fetch flow.
*/
func buildFetchAction(
	cfg *config.Config,
	dbPool *pgxpool.Pool,
) *weatheractions.FetchWeatherForLocationsAction {
	weatherAPIClient := weatherapi.NewClient(cfg)
	weatherAPIMapper := weatherapi.NewMapper(cfg)
	weatherDataSource := weatherapi.NewDataSource(weatherAPIClient, weatherAPIMapper)

	locationRepository := postgres.NewLocationRepository(dbPool)
	outboxRepository := postgres.NewOutboxRepository(dbPool)
	outboxMessageFactory := outboxservices.NewOutboxMessageFactory(cfg.App.ServiceName)

	return weatheractions.NewFetchWeatherForLocationsAction(
		locationRepository,
		weatherDataSource,
		outboxMessageFactory,
		outboxRepository,
	)
}

/*
runFetch izvršava jedan ciklus dohvata vremenskih podataka i punjenja outbox-a.
*/
func runFetch(ctx context.Context, action *weatheractions.FetchWeatherForLocationsAction) {
	createdMessages, err := action.Execute(ctx)
	if err != nil {
		log.Printf("weather fetch failed: %v", err)
		return
	}

	log.Printf("weather fetch završen. Kreirano outbox poruka: %d", createdMessages)
}

/*
runPublish izvršava jedan ciklus publish-a pending outbox poruka.
*/
func runPublish(
	ctx context.Context,
	outboxRepository *postgres.OutboxRepository,
	publisher *rabbitmq.Publisher,
) {
	messages, err := outboxRepository.LockPendingBatch(ctx, 50)
	if err != nil {
		log.Printf("outbox lock failed: %v", err)
		return
	}

	if len(messages) == 0 {
		log.Println("nema pending outbox poruka za publish")
		return
	}

	published := 0
	failed := 0

	for _, message := range messages {
		if err := publisher.Publish(ctx, message); err != nil {
			failed++
			markErr := outboxRepository.MarkAsFailed(
				ctx,
				message.ID,
				err.Error(),
				message.Attempts,
				maxOutboxAttempts,
			)
			if markErr != nil {
				log.Printf("greška pri označavanju failed outbox poruke %d: %v", message.ID, markErr)
			}

			log.Printf("publish failed za outbox message id=%d event_id=%s: %v", message.ID, message.EventID, err)
			continue
		}

		if err := outboxRepository.MarkAsPublished(ctx, message.ID); err != nil {
			failed++
			log.Printf("poruka je publishovana, ali nije označena kao published id=%d: %v", message.ID, err)
			continue
		}

		published++
	}

	log.Printf("outbox publish završen. Published=%d Failed=%d", published, failed)
}
