bash-processing:
	docker compose exec processing-core bash

restore-processing:
	docker compose exec processing-core dotnet restore src/Api/Api.csproj

build-processing:
	docker compose exec processing-core dotnet build src/Api/Api.csproj
	docker compose exec processing-core dotnet build src/Cli/Cli.csproj

test-processing:
	docker compose exec processing-core dotnet test src/ProcessingCore.Tests/ProcessingCore.Tests.csproj

run-processing:
	docker compose up -d processing-core

logs-processing:
	docker compose logs -f processing-core

migrate-processing:
	docker compose exec processing-core dotnet run --project src/Cli/Cli.csproj -- migrate

consume-processing:
	docker compose exec processing-core dotnet run --project src/Cli/Cli.csproj -- consume

cleanup-processing-history:
	docker compose exec processing-core dotnet run --project src/Cli/Cli.csproj -- cleanup-history
