<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Schedule definicije za ingestion-openmeteo
|--------------------------------------------------------------------------
|
| Za v1 želimo jednostavan i pregledan scheduler tok:
| - weather:fetch periodično dohvaća nove vremenske podatke
| - outbox:publish redovno pokušava publish pending poruka
|
*/

Schedule::command('weather:fetch')->everyMinute();
Schedule::command('outbox:publish --limit=50')->everyMinute();
