# weather-comparison

NestJS / TypeScript servis zaduzen za poredjenje vremenskih podataka iz vise izvora i realtime isporuku rezultata klijentima.

## Tech stack

- Language: TypeScript
- Runtime: Node.js
- Framework: NestJS
- Database: none in v1
- Messaging: none in v1
- HTTP client: processing-core REST API
- Realtime: Socket.IO kroz NestJS Gateway
- Architecture: layered presentation service

## Odgovornosti servisa

Ovaj servis je u v1 zaduzen za:

- citanje current snapshot podataka iz `processing-core` REST API-ja
- poredjenje podataka iz razlicitih izvora
- emitovanje realtime promena preko Socket.IO gateway-a
- izlaganje API-ja za dashboard / frontend initial load
- cuvanje poslednjeg comparison rezultata u memoriji

Servis ne cita direktno bazu `processing-core` servisa i nije source of truth za weather podatke.
Servis u v1 nema bazu i ne consume-uje RabbitMQ direktno.

## Komunikacija sa processing-core

U v1 servis poziva REST API koji izlaže `processing-core`:

```text
PROCESSING_CORE_API_BASE_URL=http://processing-core:8080
PROCESSING_CORE_POLL_INTERVAL_MS=5000
```

Polling interval kontrolise koliko cesto servis osvezava comparison i emituje Socket.IO event `weather.comparison.updated`.

## Dashboard

Servis isporucuje staticki dashboard na root ruti:

```bash
http://localhost:3001
```

Dashboard koristi:

- `GET /api/comparison/current` za inicijalno stanje
- `GET /api/comparison/history` za history, weekly, monthly i quarterly podatke
- Socket.IO event `weather.comparison.updated` za live update

## Lokalni razvoj

### Ulazak u container

```bash
make bash-weather-comparison
```

### Instalacija dependency-ja

```bash
make install-weather-comparison
```

### Pokretanje development servera

```bash
make dev-weather-comparison
```

Ova komanda pokreće Docker Compose servis. Sam container startuje NestJS aplikaciju kroz `npm run start:dev`.

### Build

```bash
make build-weather-comparison
```

### Testovi

```bash
make test-weather-comparison
```

Test komanda kompajlira TypeScript u `dist-test` i pokrece Node built-in test runner.

### Health endpoint

```bash
curl http://localhost:3001/health
```

### Current comparison endpoint

```bash
curl http://localhost:3001/api/comparison/current
```

### Location history endpoint

```bash
curl "http://localhost:3001/api/comparison/history?locationId=<uuid>&from=2026-05-01T00:00:00Z"
```

## Struktura

```text
src/
  domain/
  application/
  infrastructure/
  config/
  main.ts
```

Domain sloj ne treba da zavisi od NestJS-a, HTTP client-a, Socket.IO-a ili environment konfiguracije.
