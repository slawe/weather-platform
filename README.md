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
* `processing-core` - Laravel servis koji prima evente, obrađuje ih idempotentno i upisuje read modele
* `ingestion-weatherapi` - Go servis koji će povlačiti podatke sa WeatherAPI-ja i emitovati isti canonical event contract

Planirani sledeći servisi:

* `dashboard-realtime` - NodeJS servis za realtime prikaz podataka i websocket komunikaciju
* dodatni comparison / analytics servis
* .NET servis za dalje upoznavanje sa novim ekosistemom i širenje platforme

---

## Arhitektura sistema

Platforma je organizovana kao skup nezavisnih servisa, gde svaki servis ima jasnu odgovornost i sopstvenu bazu podataka.

Osnovni arhitektonski principi su:

* svaki servis je zaseban deployment unit
* svaki servis je vlasnik svoje baze
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
9. Rezultat se upisuje u read model bazu

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

Laravel servis zadužen za:

* consume RabbitMQ poruka
* deserializaciju incoming eventa
* routing do odgovarajućeg handlera
* idempotency proveru
* upis processed podataka u read model bazu
* retry i DLQ tok

### ingestion-weatherapi

Go servis zadužen za:

* povlačenje vremenskih podataka sa WeatherAPI-ja
* mapiranje podataka u isti canonical contract
* publish događaja u RabbitMQ

### dashboard-realtime

Planirani NodeJS servis za:

* websocket komunikaciju
* realtime dashboard
* live prikaz novih podataka i poređenja između source-ova

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
    ├── processing-core/
    ├── ingestion-weatherapi/
    └── dashboard-realtime/         # planirano
```

Svaki servis ima sopstveni README, sopstvenu internu arhitekturu i sopstvenu bazu podataka.

---

## Tehnologije

U okviru platforme koriste se ili će se koristiti sledeće tehnologije:

* Laravel / PHP
* Go
* NodeJS
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

### Instalacija dependencija za postojeće Laravel servise

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
* `weather_snapshots`

### ingestion-weatherapi baza

Planirana za:

* `locations`
* `outbox_messages`

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

## Roadmap

Planirani sledeći koraci razvoja platforme su:

1. završetak Go servisa `ingestion-weatherapi`
2. dodavanje NodeJS servisa `dashboard-realtime`
3. dodavanje comparison / analytics servisa
4. dodavanje zasebnog .NET servisa
5. proširenje canonical event contract-a po potrebi
6. dodavanje dodatnih source provider-a
7. unapređenje observability i monitoring priče

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

