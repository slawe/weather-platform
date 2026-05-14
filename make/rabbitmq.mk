rabbit:
	@echo "RabbitMQ UI: http://localhost:15672  (demo/demo)"

rabbitmq-setup:
	docker compose exec processing-core php artisan rabbitmq:setup
