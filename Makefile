include make/docker.mk
include make/rabbitmq.mk
include make/ingestion-openmeteo.mk
include make/ingestion-weatherapi.mk
include make/processing-core.mk
include make/weather-comparison.mk
include make/aliases.mk

composer-install:
	$(MAKE) composer-install-openmeteo

keygen:
	$(MAKE) keygen-openmeteo

optimize-clear:
	$(MAKE) optimize-clear-openmeteo

setup-weather-comparison: install-weather-comparison

migrate-all: migrate-openmeteo migrate-processing

seed-all: seed-openmeteo setup-weatherapi

setup: composer-install keygen restore-processing install-weather-comparison migrate-all seed-all rabbitmq-setup

test: test-processing test-weather-comparison

fetch-all: fetch-openmeteo fetch-weatherapi

publish-all: publish-openmeteo publish-weatherapi

ingest-once: fetch-all publish-all
