# ingestion-openmeteo

Laravel servis zadužen za periodično preuzimanje vremenskih podataka sa Open-Meteo API-ja, kreiranje domen događaja i upis integration poruka u outbox tabelu, a zatim i publish tih poruka na RabbitMQ.

## Odgovornosti servisa

Ovaj servis je zadužen za:

- dohvat vremenskih podataka za aktivne lokacije
- mapiranje spoljnog API odgovora u interni DTO
- kreiranje domen događaja kroz agregat
- upis događaja u outbox tabelu
- publish outbox poruka na RabbitMQ

Servis trenutno koristi **Open-Meteo** kao izvor podataka, ali je struktura pripremljena za buduće proširenje sa dodatnim provider-ima.

---

## Arhitektura

Servis je organizovan po slojevima:

- `Domain/`  
  Domen modeli, agregat i domain eventi

- `Application/`  
  Use case logika, DTO objekti i contracts

- `Infrastructure/`  
  Eloquent modeli, repository implementacije, Open-Meteo source i RabbitMQ publish sloj

- `Console/Commands/`  
  Artisan komande za ručno pokretanje ingestion i outbox publish procesa

---

## Glavni tok rada

Tok rada izgleda ovako:

1. Scheduler ili ručna komanda pokrene `weather:fetch`
2. Servis učita aktivne lokacije iz baze
3. Za svaku lokaciju pozove Open-Meteo API
4. Raw API odgovor mapira u `WeatherSnapshotData`
5. Agregat `WeatherIngestion` beleži domain event `WeatherSnapshotFetched`
6. Domain eventi se prevode u outbox poruke
7. Outbox poruke se čuvaju u tabeli `outbox_messages`
8. Komanda `outbox:publish` uzima pending poruke i šalje ih na RabbitMQ

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

Servis publikuje poruke na RabbitMQ exchange definisan u konfiguraciji.

### V1 routing key

- `weather.snapshot.fetched`

---

## Važne Artisan komande

### Dohvat vremenskih podataka i punjenje outbox-a

```bash
php artisan weather:fetch
```

### Publish pending outbox poruka

```bash
php artisan outbox:publish
```


### php artisan outbox:publish

```bash
php artisan schedule:list
```

### Ručno pokretanje schedulera
```bash
php artisan schedule:run
```

---

## Lokalni razvoj

### Pokretanje containera iz root projekta
```bash
make up
```

### Instalacija dependencija
```bash
make composer-install
```

### Generisanje app key
```bash
make keygen
```

### Čišćenje Laravel cache-a
```bash
make optimize-clear-openmeteo
```

### Sveže migracije i seed
```bash
make fresh-seed-openmeteo
```

### Lista dostupnih komandi
```bash
make commands-openmeteo
```

### Ručno pokretanje ingestion procesa
```bash
make fetch-openmeteo
```

### Ručno pokretanje outbox publish procesa
```bash
make publish-openmeteo
```

---

## Scheduler

Scheduler definicije su postavljene tako da:

- `weather:fetch` radi periodično
- `outbox:publish` pokušava publish pending poruka u kraćim intervalima

Za lokalni razvoj scheduler se može testirati ručno:

```bash
php artisan schedule:run
```

---

## Seed podaci

Početne lokacije se seeduju iz `config/weather.php`.

To omogućava brz lokalni start bez ručnog unosa lokacija u bazu.

---

## Napomena o event-driven pristupu

Ovaj servis ne šalje poruke direktno iz use case logike.

Umesto toga koristi **Outbox pattern**:

- domen i aplikacioni sloj kreiraju outbox zapis
- poseban publish korak šalje poruku na broker

Time se dobija pouzdaniji i čistiji event-driven tok.

---

## Sledeći koraci

Planirani sledeći koraci u okviru platforme:

- `processing-core` servis za obradu incoming eventa
- dodatni ingestion servis iz drugog source-a
- comparison servis za poređenje podataka iz više izvora
- realtime dashboard servis

---
