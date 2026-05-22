# processing-core

ASP.NET Core / C# servis zaduzen za obradu canonical weather eventa, cuvanje aktuelnog weather stanja, kontrolisanu istoriju znacajnih promena i REST API za citanje podataka.

## Tech stack

- Language: C#
- Framework: ASP.NET Core Web API
- Database: PostgreSQL
- DB access: Dapper ili direktan Npgsql
- Messaging: RabbitMQ.Client
- Background processing: hosted worker service
- Architecture: DDD / layered + event-driven

## Odgovornosti servisa

Ovaj servis je source of truth za obradjene weather podatke.

Servis treba da:

- consume-uje `weather.snapshot.fetched` evente iz RabbitMQ-a
- validira canonical event envelope
- obezbedi idempotency preko `consumed_events`
- upsertuje trenutno stanje u `weather_snapshots_current`
- upisuje znacajne promene u `weather_snapshots_history`
- izlozi REST API za current, history i average podatke
- pokrece retention cleanup za history zapise

## Lokalni razvoj

### Ulazak u container

```bash
make bash-processing
```

### Restore dependency-ja

```bash
make restore-processing
```

### Build

```bash
make build-processing
```

CLI komande za migracije, RabbitMQ setup, debug consumer i ručni cleanup nalaze se u `src/Cli`.

### Testovi

```bash
make test-processing
```

### Pokretanje API-ja

```bash
make run-processing
```

`processing-core` se u Docker Compose režimu pokreće kao ASP.NET Core API i u istom procesu startuje hosted RabbitMQ consumer. Za normalan lokalni rad nije potrebno posebno pokretati `make consume-processing`.

### Health endpoint

```bash
curl http://localhost:8082/api/health
```

### Weather API endpointi

```bash
curl http://localhost:8082/api/weather/snapshots/current
curl "http://localhost:8082/api/weather/snapshots/history?locationId=<uuid>&source=open-meteo"
curl "http://localhost:8082/api/weather/averages/weekly?locationId=<uuid>&source=open-meteo"
curl "http://localhost:8082/api/weather/averages/monthly?locationId=<uuid>&source=open-meteo"
curl "http://localhost:8082/api/weather/averages/quarterly?locationId=<uuid>&source=open-meteo"
```

### Migracije

```bash
make migrate-processing
```

### History cleanup

```bash
make cleanup-processing-history
```

API proces automatski pokreće history retention cleanup na startu i zatim ga ponavlja na interval definisan kroz:

```text
WEATHER_HISTORY_RETENTION_CLEANUP_INTERVAL_HOURS=24
```

### Ručni consumer debug

```bash
make consume-processing
```

Ovu komandu koristiti samo za debug kada glavni `processing-core` servis nije aktivan, jer aktivan API proces već sluša RabbitMQ queue kroz hosted consumer.

## Tabele

- `consumed_events`
- `weather_snapshots_current`
- `weather_snapshots_history`

`weather_snapshots_current` uvek predstavlja poslednje poznato stanje po `location_id + source`.

`weather_snapshots_history` nije raw log svakog request-a. Novi red se dodaje samo kada postoji znacajna promena: temperatura, brzina vetra, weather code ili novi dan po `observed_at`.

Retention cleanup brise samo zapise iz `weather_snapshots_history` koji su stariji od `WEATHER_HISTORY_RETENTION_DAYS`. Current read model se ne brise ni automatskim cleanup-om ni ručnom komandom.
