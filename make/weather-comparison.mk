bash-weather-comparison:
	docker compose exec weather-comparison sh

install-weather-comparison:
	docker compose exec weather-comparison npm install

build-weather-comparison:
	docker compose exec weather-comparison npm run build

dev-weather-comparison:
	docker compose exec weather-comparison npm run start:dev

start-weather-comparison:
	docker compose exec weather-comparison npm run start

migrate-weather-comparison:
	docker compose exec weather-comparison npm run migrate

create-migration-weather-comparison:
	docker compose exec weather-comparison npm run migration:create
