# ingestion-weatherapi

Go servis zadužen za periodično preuzimanje vremenskih podataka sa WeatherAPI-ja, kreiranje domen događaja i upis integration poruka u outbox tabelu, a zatim i publish tih poruka na RabbitMQ.

## Odgovornosti servisa

Ovaj servis je zadužen za:

- preuzimanje vremenskih podataka za aktivne lokacije
- mapiranje WeatherAPI odgovora u canonical domain model
- kreiranje domain eventa kroz agregat
- upis događaja u outbox tabelu
- publish outbox poruka na RabbitMQ
- periodično pokretanje fetch i publish procesa kroz Go scheduler

Servis emituje isti canonical event contract kao `ingestion-openmeteo`, tako da `processing-core` ne mora da zna iz kog source-a je poruka došla.

---

## Arhitektura

Servis je organizovan po slojevima:

- `internal/domain/`  
  Domen modeli, agregat i domain eventi

- `internal/application/`  
  Use case logika, DTO objekti i contracts

- `internal/infrastructure/`  
  Postgres repository implementacije, WeatherAPI source adapter i RabbitMQ publisher

- `cmd/`  
  Go entrypoint-i za setup, jednokratni fetch, outbox publish i scheduler

---

## Glavni tok rada

Tok rada izgleda ovako:

1. Scheduler ili ručna komanda pokrene WeatherAPI fetch
2. Servis učita aktivne lokacije iz svoje baze
3. Za svaku lokaciju pozove WeatherAPI current endpoint
4. Raw API odgovor mapira u `WeatherSnapshotData`
5. Agregat `WeatherIngestion` beleži domain event `WeatherSnapshotFetched`
6. Domain eventi se prevode u outbox poruke
7. Outbox poruke se čuvaju u tabeli `outbox_messages`
8. Publish proces uzima pending poruke i šalje ih na RabbitMQ exchange `weather.events`

---

## Baza

Servis koristi sopstvenu Postgres bazu.

### Tabele

#### `locations`
Čuva lokacije za koje se preuzimaju vremenski podaci.

#### `outbox_messages`
Čuva integration poruke koje čekaju publish ili su već publikovane.

---

## RabbitMQ

Servis publikuje poruke na RabbitMQ exchange definisan kroz environment konfiguraciju.

### V1 routing key

- `weather.snapshot.fetched`

RabbitMQ queue topologiju poseduje `processing-core`. Pre publish-a iz root projekta koristi se `make rabbitmq-setup`, ili Make target-i koji publish-uju poruke to rade automatski kao dependency.

---

## Go komande

### Setup baze i seed lokacija

```bash
go run ./cmd/setup
```

### Jednokratni fetch i punjenje outbox-a

```bash
go run ./cmd/app
```

### Publish pending outbox poruka

```bash
go run ./cmd/publish-outbox
```

### Periodični scheduler

```bash
go run ./cmd/scheduler
```

Scheduler odmah pokreće fetch i publish, zatim ih ponavlja na intervale iz konfiguracije.

---

## Lokalni razvoj

### Pokretanje containera iz root projekta

```bash
make up
```

### Setup WeatherAPI servisa

```bash
make setup-weatherapi
```

### Jednokratni fetch

```bash
make fetch-weatherapi
```

### Publish outbox poruka

```bash
make publish-weatherapi
```

### Pokretanje schedulera

```bash
make schedule-weatherapi
```

---

## Konfiguracija

Ključne environment varijable:

- `WEATHERAPI_BASE_URL`
- `WEATHERAPI_KEY`
- `RABBITMQ_EXCHANGE`
- `RABBITMQ_ROUTING_KEY`
- `WEATHER_FETCH_INTERVAL_SECONDS`
- `OUTBOX_PUBLISH_INTERVAL_SECONDS`

Default intervali schedulera su 60 sekundi za fetch i 60 sekundi za outbox publish.

---

## Napomena o outbox pattern-u

Servis ne šalje događaj direktno iz use case logike.

Umesto toga:

- fetch proces kreira outbox zapis
- publish proces šalje pending outbox poruke na RabbitMQ

Ovo razdvaja preuzimanje podataka od komunikacije sa brokerom i prati isti obrazac koji koristi Laravel ingestion servis.
