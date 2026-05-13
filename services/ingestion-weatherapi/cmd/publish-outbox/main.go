package main

import (
	"context"
	"fmt"
	"log"

	"ingestion-weatherapi/internal/config"
	"ingestion-weatherapi/internal/infrastructure/messaging/rabbitmq"
	"ingestion-weatherapi/internal/infrastructure/persistence/postgres"
)

/*
Publish outbox command čita pending outbox poruke i objavljuje ih na RabbitMQ.

Ovo je Go ekvivalent Laravel komande:
php artisan outbox:publish
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

	outboxRepository := postgres.NewOutboxRepository(dbPool)
	publisher := rabbitmq.NewPublisher(cfg)

	messages, err := outboxRepository.LockPendingBatch(ctx, 50)
	if err != nil {
		log.Fatalf("greška pri zaključavanju pending outbox poruka: %v", err)
	}

	if len(messages) == 0 {
		fmt.Println("Nema pending outbox poruka za publish.")
		return
	}

	published := 0
	failed := 0
	maxAttempts := 5

	for _, message := range messages {
		if err := publisher.Publish(ctx, message); err != nil {
			failed++

			if markErr := outboxRepository.MarkAsFailed(
				ctx,
				message.ID,
				err.Error(),
				message.Attempts,
				maxAttempts,
			); markErr != nil {
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

	fmt.Printf("Outbox publish završen. Published=%d Failed=%d\n", published, failed)
}
