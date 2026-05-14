include make/docker.mk
include make/rabbitmq.mk
include make/ingestion-openmeteo.mk
include make/ingestion-weatherapi.mk
include make/processing-core.mk
include make/aliases.mk

composer-install:
	$(MAKE) composer-install-openmeteo
	$(MAKE) composer-install-processing

keygen:
	$(MAKE) keygen-openmeteo
	$(MAKE) keygen-processing

optimize-clear:
	$(MAKE) optimize-clear-openmeteo
	$(MAKE) optimize-clear-processing

migrate-all: migrate-openmeteo migrate-processing

seed-all: seed-openmeteo setup-weatherapi

setup: composer-install keygen migrate-all seed-all rabbitmq-setup

fetch-all: fetch-openmeteo fetch-weatherapi

publish-all: publish-openmeteo publish-weatherapi

ingest-once: fetch-all publish-all
