package main

import (
	"context"
	"fmt"
	"log"

	"ingestion-weatherapi/internal/config"
	"ingestion-weatherapi/internal/infrastructure/persistence/postgres"
)

/*
Setup command pokreće migracije i seed za lokalni razvoj.

Ovo je Go ekvivalent nečega poput:
php artisan migrate --seed

Namerno je odvojeno od glavne app komande,
da svaki ingestion run ne pokreće migracije.
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

	if err := postgres.RunMigrations(ctx, dbPool); err != nil {
		log.Fatalf("greška pri izvršavanju migracija: %v", err)
	}

	if err := postgres.SeedLocations(ctx, dbPool, cfg); err != nil {
		log.Fatalf("greška pri seed-u lokacija: %v", err)
	}

	fmt.Println("ingestion-weatherapi setup uspešno izvršen")
}
