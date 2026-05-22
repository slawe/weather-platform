# Weather Platform

Weather Platform je lokalna mikroservisna platforma za ingestiju, obradu, poređenje i prikaz vremenskih podataka iz više izvora.

Cilj projekta je da kroz više nezavisnih servisa, napisanih u različitim programskim jezicima, demonstrira:

* mikroservisnu arhitekturu
* DDD i layered pristup
* event-driven komunikaciju preko RabbitMQ
* outbox pattern
* idempotentnu obradu događaja
* retry i DLQ tokove
* proširiv sistem spreman za više source-ova i više tehnoloških stack-ova

Trenutno platforma sadrži sledeće servise:

* `ingestion-openmeteo` - Laravel servis koji povlači vremenske podatke sa Open-Meteo API-ja i publikuje integration evente
* `ingestion-weatherapi` - Go servis koji povlači podatke sa WeatherAPI-ja i emituje isti canonical event contract
* `processing-core` - ASP.NET Core servis koji consume-uje evente, čuva current/history podatke i izlaže REST API
* `weather-comparison` - NestJS / TypeScript presentation servis za poređenje, istorijski prikaz i realtime isporuku rezultata

---

## Arhitektura sistema

Platforma je organizovana kao skup nezavisnih servisa, gde svaki servis ima jasnu odgovornost i sopstvenu bazu podataka.

Osnovni arhitektonski principi su:

* svaki servis je zaseban deployment unit
* backend servisi koji čuvaju stanje vlasnici su svojih baza
* servisi komuniciraju preko RabbitMQ
* integration eventi koriste isti canonical contract
* domen, aplikacioni sloj i infrastruktura su razdvojeni
* source-specific logika je izolovana od ostatka sistema

### Trenutni tok rada

1. Ingestion servis preuzima podatke iz spoljnog API-ja
2. Podatke mapira u interni canonical format
3. Agregat kreira domain event
4. Domain event se prevodi u outbox poruku
5. Outbox poruka se publikuje na RabbitMQ
6. `processing-core` prima poruku
7. Poruka se deserializuje i routuje do odgovarajućeg handlera
8. Obrada se izvršava idempotentno
9. Current stanje se upisuje u `weather_snapshots_current`
10. Značajne promene se upisuju u `weather_snapshots_history`
11. `weather-comparison` čita REST API i računa poređenje za frontend

### RabbitMQ uloga

RabbitMQ služi kao message broker između servisa i omogućava:

* asinhronu komunikaciju
* slabo vezane servise
* retry tok
* dead-letter queue mehanizam
* lakše dodavanje novih consumer-a i producer-a

---

## Servisi

### ingestion-openmeteo

Laravel servis zadužen za:

* povlačenje vremenskih podataka sa Open-Meteo API-ja
* mapiranje podataka u canonical payload
* kreiranje domain eventa kroz agregat
* upis outbox poruka
* publish poruka na RabbitMQ

### processing-core

ASP.NET Core / C# servis zadužen za:

* consume RabbitMQ poruka
* validaciju canonical event envelope-a
* idempotency proveru
* upsert current weather stanja
* upis kontrolisane istorije značajnih promena
* REST API za current, history i average podatke
* retry i DLQ tok

### ingestion-weatherapi

Go servis zadužen za:

* preuzimanje vremenskih podataka sa WeatherAPI-ja
* mapiranje podataka u isti canonical contract
* upis outbox poruka
* publish događaja u RabbitMQ
* periodični fetch i outbox publish kroz Go scheduler

### weather-comparison

NestJS / TypeScript servis zadužen za:

* pozivanje `processing-core` REST API-ja
* poređenje vrednosti između različitih weather source-ova
* prikaz nedeljnih, mesečnih i tromesečnih trendova po lokaciji
* realtime isporuku rezultata preko Socket.IO gateway-a
* budući dashboard/API sloj za prikaz poređenja

---

## Canonical event contract

Svi ingestion servisi, bez obzira na jezik ili source provider, treba da emituju isti integration event format.

Primer canonical event envelope-a:

```json
{
  "event_id": "uuid",
  "event_name": "weather.snapshot.fetched",
  "event_version": 1,
  "occurred_at": "2026-03-30T12:00:00Z",
  "producer": "ingestion-openmeteo",
  "payload": {
    "location_id": "uuid",
    "city": "Belgrade",
    "country": "Serbia",
    "latitude": 44.8178,
    "longitude": 20.4569,
    "temperature_c": 17.3,
    "wind_speed_kmh": 4.8,
    "weather_code": 1003,
    "observed_at": "2026-03-30T12:00:00Z",
    "source": "open-meteo"
  }
}
```

Ovaj contract omogućava da consumer servisi ne moraju da znaju iz kog jezika ili source-a poruka dolazi, već da rade samo sa stabilnim i unapred definisanim formatom.

---

## Struktura projekta

```text
weather-platform/
├── .docker/
├── compose/
├── docker-compose.yml
├── Makefile
├── .env
└── services/
    ├── ingestion-openmeteo/
    ├── ingestion-weatherapi/
    ├── processing-core/
    └── weather-comparison/
```

Svaki servis ima sopstveni README i sopstvenu internu arhitekturu. Servisi koji čuvaju stanje imaju sopstvenu bazu.

---

## Tehnologije

U okviru platforme koriste se ili će se koristiti sledeće tehnologije:

* Laravel / PHP
* Go
* NodeJS / TypeScript / NestJS
* .NET
* PostgreSQL
* RabbitMQ
* Docker Compose

Cilj nije samo tehnička raznolikost, već i upoznavanje sa različitim pristupima implementaciji iste event-driven arhitekture u različitim jezicima i ekosistemima.

---

## Lokalni razvoj

### Pokretanje svih servisa

```bash
make up
```

`processing-core` se pokreće kao ASP.NET Core API sa hosted RabbitMQ consumer-om. `weather-comparison` se pokreće kao NestJS development servis i periodično čita `processing-core` REST API.

### Gašenje svih servisa

```bash
make down
```

### Pregled aktivnih containera

```bash
make ps
```

### Pregled logova

```bash
make logs
```

### Pokretanje testova

```bash
make test
```

Trenutno pokreće testove za `.NET processing-core` i `weather-comparison`.

### Instalacija dependency-ja za Laravel producer

```bash
make composer-install
```

### Generisanje Laravel app key vrednosti

```bash
make keygen
```

### RabbitMQ management UI

RabbitMQ je dostupan na:

```text
http://localhost:15672
```

Podrazumevani kredencijali:

* korisnik: `demo`
* lozinka: `demo`

### Weather comparison dashboard

Dashboard je dostupan na:

```text
http://localhost:3001
```

### RabbitMQ topologija

`processing-core` poseduje RabbitMQ queue topologiju i može je deklarisati nezavisno od consumer procesa:

```bash
make rabbitmq-setup
```

Ova komanda kreira:

* `weather.events` exchange
* `weather.processing`
* `weather.processing.retry`
* `weather.processing.dlq`

Publish target-i za ingestion servise prvo pokreću ovaj setup, kako poruke ne bi zavisile od toga da li je consumer već startovan.

---

## Baze i ownership

Svaki servis ima svoju bazu i ne deli ownership nad podacima sa drugim servisima.

### ingestion-openmeteo baza

Koristi se za:

* `locations`
* `outbox_messages`

### processing-core baza

Koristi se za:

* `consumed_events`
* `weather_snapshots_current`
* `weather_snapshots_history`

### ingestion-weatherapi baza

Koristi se za:

* `locations`
* `outbox_messages`

### weather-comparison

U v1 nema sopstvenu bazu. Čita `processing-core` REST API i drži poslednji comparison rezultat u memoriji.

Ovakav pristup zadržava nezavisnost servisa i sprečava prelivanje odgovornosti između bounded context-a.

---

## Event-driven principi

Platforma koristi event-driven pristup kao osnovni način komunikacije između servisa.

Ključne ideje su:

* producer ne poziva consumer direktno
* producer emituje event
* consumer samostalno odlučuje kako će event obraditi
* obrada mora biti idempotentna
* greške se rešavaju kroz retry i DLQ tok
* outbox pattern obezbeđuje pouzdaniji publish događaja

Ovakav pristup omogućava lakše dodavanje novih servisa bez menjanja postojećih producer-a.

---

## Trenutno stanje

Platforma trenutno podrzava kompletan lokalni tok:

1. `ingestion-openmeteo` fetchuje podatke i upisuje outbox poruke
2. `ingestion-weatherapi` fetchuje podatke i upisuje outbox poruke
3. oba producer-a publikuju canonical `weather.snapshot.fetched` evente
4. `processing-core` consume-uje evente kroz hosted RabbitMQ consumer
5. `processing-core` idempotentno azurira current stanje i kontrolisanu history tabelu
6. `processing-core` izlaže REST API za current, history i average podatke
7. `processing-core` automatski pokreće retention cleanup za zastarele history zapise
8. `weather-comparison` cita `processing-core` REST API
9. `weather-comparison` racuna comparison i emituje Socket.IO update
10. dashboard na `http://localhost:3001` prikazuje trenutno poredjenje i history detail po gradu

## V1 status

V1 lokalni demo tok je zatvoren kada sledeće provere prolaze:

```bash
make setup
make test
make ingest-once
```

Očekivano current stanje posle oba producer-a je:

```text
3 lokacije × 2 source-a = 6 redova
```

Planirani sledeci koraci posle stabilizacije osnovnog toka su:

1. dodavanje dodatnih source provider-a
2. uvodjenje observability i monitoring priče
3. prosirenje dashboard prikaza trendovima iz history podataka
4. priprema production-friendly Docker buildova

---

## Svrha projekta

Ovaj projekat služi kao praktična platforma za:

* širenje mikroservisne arhitekture
* rad sa event-driven sistemima
* upoznavanje više programskih jezika kroz isti domen
* vežbanje DDD i layered pristupa
* rad sa RabbitMQ, outbox pattern-om i idempotency mehanizmima
* građenje ozbiljnijeg portfolio projekta koji ima realnu arhitektonsku primenu

---
