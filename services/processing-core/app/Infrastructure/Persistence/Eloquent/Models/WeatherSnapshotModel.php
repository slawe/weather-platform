<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model za read model tabelu weather_snapshots.
 *
 * Ovaj model predstavlja rezultat obrade incoming weather eventa
 * u processing servisu.
 */
class WeatherSnapshotModel extends Model
{
    protected $table = 'weather_snapshots';

    protected $fillable = [
        'event_id',
        'location_id',
        'city',
        'country',
        'latitude',
        'longitude',
        'temperature_c',
        'wind_speed_kmh',
        'weather_code',
        'observed_at',
        'source',
        'received_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'temperature_c' => 'float',
        'wind_speed_kmh' => 'float',
        'weather_code' => 'integer',
        'observed_at' => 'datetime',
        'received_at' => 'datetime',
    ];
}
