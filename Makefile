up:
	docker compose up -d --build

down:
	docker compose down -v

restart:
	docker compose down
	docker compose up -d --build

ps:
	docker compose ps

logs:
	docker compose logs -f --tail=200

bash-ingestion:
	docker compose exec ingestion-openmeteo bash

bash-processing:
	docker compose exec processing-core bash

composer-install:
	docker compose exec ingestion-openmeteo composer install
	docker compose exec processing-core composer install

keygen:
	docker compose exec ingestion-openmeteo php artisan key:generate
	docker compose exec processing-core php artisan key:generate

optimize-clear:
	docker compose exec ingestion-openmeteo php artisan optimize:clear
	docker compose exec processing-core php artisan optimize:clear

migrate-ingestion:
	docker compose exec ingestion-openmeteo php artisan migrate

migrate-processing:
	docker compose exec processing-core php artisan migrate

seed-ingestion:
	docker compose exec ingestion-openmeteo php artisan db:seed

fresh-seed-ingestion:
	docker compose exec ingestion-openmeteo php artisan migrate:fresh --seed

tinker-ingestion:
	docker compose exec ingestion-openmeteo php artisan tinker

rabbit:
	@echo "RabbitMQ UI: http://localhost:15672  (demo/demo)"

list-ingestion-commands:
	docker compose exec ingestion-openmeteo php artisan list | grep -E "weather|outbox"

fetch-weather:
	docker compose exec ingestion-openmeteo php artisan weather:fetch

publish-outbox:
	docker compose exec ingestion-openmeteo php artisan outbox:publish

consume-weather:
	docker compose exec processing-core php artisan weather:consume

logs-processing:
	docker compose exec processing-core tail -f storage/logs/laravel.log

