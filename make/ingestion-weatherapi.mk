bash-weatherapi:
	docker compose exec ingestion-weatherapi sh

setup-weatherapi:
	docker compose exec ingestion-weatherapi go run ./cmd/setup

fetch-weatherapi:
	docker compose exec ingestion-weatherapi go run ./cmd/app

publish-weatherapi: rabbitmq-setup
	docker compose exec ingestion-weatherapi go run ./cmd/publish-outbox

schedule-weatherapi: rabbitmq-setup
	docker compose exec ingestion-weatherapi go run ./cmd/scheduler
