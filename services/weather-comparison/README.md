# weather-comparison

NestJS / TypeScript servis zaduzen za poredjenje vremenskih podataka iz vise izvora i realtime isporuku rezultata klijentima.

## Tech stack

- Language: TypeScript
- Runtime: Node.js
- Framework: NestJS
- Database: PostgreSQL
- DB access: Kysely
- Messaging: RabbitMQ preko `amqplib`
- Realtime: Socket.IO kroz NestJS Gateway
- Architecture: DDD / layered + event-driven

## Odgovornosti servisa

Ovaj servis ce biti zaduzen za:

- consume obradjenih vremenskih update eventa iz RabbitMQ-a
- cuvanje sopstvenog read modela u PostgreSQL bazi
- poredjenje podataka iz razlicitih izvora
- emitovanje realtime promena preko Socket.IO gateway-a
- izlaganje API-ja za dashboard / frontend prikaz

Servis ne cita direktno bazu `processing-core` servisa.

## Planirani event

`processing-core` treba da publikuje current-state event:

```text
weather.snapshot.updated
```

`weather-comparison` ce taj event konsumirati i koristiti za sopstveni read model.

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

### Build

```bash
make build-weather-comparison
```

### Health endpoint

```bash
curl http://localhost:3001/health
```

### Migracije

```bash
make migrate-weather-comparison
```

Trenutno nema konkretnih domain tabela. Migracioni setup postoji od pocetka, a tabele ce biti dodate kada read model bude jasno definisan.

## Struktura

```text
src/
  domain/
  application/
  infrastructure/
  config/
  main.ts
```

Domain sloj ne treba da zavisi od NestJS-a, Kysely-ja, RabbitMQ-a, Socket.IO-a ili environment konfiguracije.
