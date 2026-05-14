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
