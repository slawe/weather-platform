bash-openmeteo:
	docker compose exec ingestion-openmeteo bash

composer-install-openmeteo:
	docker compose exec ingestion-openmeteo composer install

keygen-openmeteo:
	docker compose exec ingestion-openmeteo php artisan key:generate

optimize-clear-openmeteo:
	docker compose exec ingestion-openmeteo php artisan optimize:clear

migrate-openmeteo:
	docker compose exec ingestion-openmeteo php artisan migrate

seed-openmeteo:
	docker compose exec ingestion-openmeteo php artisan db:seed

fresh-seed-openmeteo:
	docker compose exec ingestion-openmeteo php artisan migrate:fresh --seed

tinker-openmeteo:
	docker compose exec ingestion-openmeteo php artisan tinker

commands-openmeteo:
	docker compose exec ingestion-openmeteo php artisan list | grep -E "weather|outbox"

fetch-openmeteo:
	docker compose exec ingestion-openmeteo php artisan weather:fetch

publish-openmeteo: rabbitmq-setup
	docker compose exec ingestion-openmeteo php artisan outbox:publish

schedule-openmeteo:
	docker compose exec ingestion-openmeteo php artisan schedule:run
