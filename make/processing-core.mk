bash-processing:
	docker compose exec processing-core bash

composer-install-processing:
	docker compose exec processing-core composer install

keygen-processing:
	docker compose exec processing-core php artisan key:generate

optimize-clear-processing:
	docker compose exec processing-core php artisan optimize:clear

migrate-processing:
	docker compose exec processing-core php artisan migrate

fresh-processing:
	docker compose exec processing-core php artisan migrate:fresh

consume-processing:
	docker compose exec processing-core php artisan weather:consume

logs-processing:
	docker compose exec processing-core tail -f storage/logs/laravel.log
