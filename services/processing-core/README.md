# processing-core

Laravel servis zadužen za obradu incoming događaja sa RabbitMQ-a, idempotentnu obradu poruka i upis lokalnog read modela sa vremenskim snapshot-ima.

## Odgovornosti servisa

Ovaj servis je zadužen za:

- prijem integration event poruka sa RabbitMQ-a
- deserializaciju incoming event envelope-a
- routing poruke do odgovarajućeg handlera
- idempotentnu obradu događaja
- upis obrađenih podataka u lokalni read model
- retry i DLQ tok za neuspešne obrade

Za v1 servis obrađuje jedan event tip:

- `weather.snapshot.fetched`

---

## Arhitektura

Servis je organizovan po slojevima:

- `Application/`  
  DTO objekti, contracts, handleri, router i idempotency servis

- `Infrastructure/`  
  Eloquent modeli, repository implementacije, RabbitMQ consumer i serializer/deserializer sloj

- `Console/Commands/`  
  Artisan komanda za pokretanje consumer procesa

---

## Glavni tok rada

Tok rada izgleda ovako:

1. `weather:consume` pokreće RabbitMQ consumer
2. Consumer deklariše exchange, glavnu queue, retry queue i DLQ
3. Poruka stiže sa queue `weather.processing`
4. Poruka se deserializuje u `IncomingMessage`
5. `MessageRouter` bira odgovarajući handler
6. `IdempotencyService` proverava da li je event već obrađen
7. Ako nije obrađen, handler upisuje podatke u `weather_snapshots`
8. Obrada se beleži u `consumed_events`
9. Ako obrada pukne, poruka ide u retry ili DLQ tok

---

## Baza

Servis koristi sopstvenu Postgres bazu.

### Tabele

#### `consumed_events`
Tabela za idempotency mehanizam.

Čuva evidenciju već obrađenih eventa kako bi se sprečila dupla obrada.

#### `weather_snapshots`
Lokalni read model sa obrađenim vremenskim snapshot-ima.

Ova tabela predstavlja rezultat obrade eventa `weather.snapshot.fetched`.

---

## RabbitMQ

Servis troši poruke sa RabbitMQ-a.

### Queue topologija

- `weather.processing`
- `weather.processing.retry`
- `weather.processing.dlq`

### Event koji servis obrađuje

- `weather.snapshot.fetched`

---

## Važne Artisan komande

### Pokretanje RabbitMQ consumer-a

```bash
php artisan weather:consume
```

### Čišćenje Laravel cache-a

```bash
php php artisan optimize:clear
```

### Migracije baze

```bash
php artisan migrate
```

### Sveže migracije

```bash
php artisan migrate:fresh
```

---

## Lokalni razvoj

## Pokretanje containera iz root projekta
```bash
make up
```

## Instalacija dependencija
```bash
make composer-install
```

## Generisanje app key
```bash
make keygen
```

## Čišćenje Laravel cache-a
```bash
make optimize-clear-processing
```

## Sveže migracije processing baze
```bash
make fresh-processing
```

## Pokretanje consumer procesa
```bash
make consume-processing
```

## Praćenje processing logova
```bash
make logs-processing
```

---

## Kako testirati servis

### Kako testirati servis
```bash
make consume-processing
```

### Terminal 2 — pošalji poruke iz ingestion servisa
```bash
make fetch-openmeteo
make publish-openmeteo
```

### Provera rezultata

U `processing-core` bazi proveri:
- `consumed_events`
- `weather_snapshots`

U RabbitMQ UI proveri da su queue-evi prazni:
- `weather.processing`
- `weather.processing.retry`
- `weather.processing.dlq`

Healthy stanje posle uspešne obrade:
- processing = 0
- retry = 0
- dlq = 0

---

## Retry i DLQ tok

Ako obrada poruke ne uspe:

1. poruka se republishuje u `weather.processing.retry`
2. posle TTL isteka RabbitMQ je vraća nazad u glavni tok 
3. ako broj pokušaja pređe dozvoljeni maksimum, poruka ide u `weather.processing.dlq`

Ovaj mehanizam sprečava gubitak poruka i omogućava kontrolisanu obradu grešaka.

---

## Napomena o event-driven pristupu

`processing-core` ne zna ništa o Open-Meteo API-ju niti o producer domenu.

Servis radi isključivo sa canonical integration event porukama koje prima sa brokera.

Time je postignuto:
- razdvajanje producer i consumer odgovornosti
- idempotentna obrada
- stabilan event-driven tok
- mogućnost dodavanja novih producer servisa bez menjanja osnovne logike consumer-a

---

## Sledeći koraci

Planirani sledeći koraci u okviru platforme:
- dodatni ingestion servis iz drugog source-a
- comparison servis za poređenje podataka iz više izvora
- realtime dashboard servis

---
