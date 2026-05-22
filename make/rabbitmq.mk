rabbit:
	@echo "RabbitMQ UI: http://localhost:15672  (demo/demo)"

rabbitmq-setup:
	docker compose exec processing-core dotnet run --project src/Cli/Cli.csproj -- setup-rabbitmq
