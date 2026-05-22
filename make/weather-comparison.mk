bash-weather-comparison:
	docker compose exec weather-comparison sh

install-weather-comparison:
	docker compose exec weather-comparison npm install

build-weather-comparison:
	docker compose exec weather-comparison npm run build

test-weather-comparison:
	docker compose exec weather-comparison npm test

dev-weather-comparison:
	docker compose up -d weather-comparison

start-weather-comparison:
	docker compose up -d weather-comparison

logs-weather-comparison:
	docker compose logs -f weather-comparison
