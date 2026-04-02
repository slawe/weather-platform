<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model za tabelu locations.
 *
 * Ovo je infrastrukturni model i ne treba ga mešati sa domain entitetom Location.
 * Domain model služi za biznis logiku, a Eloquent model za persistence detalje.
 */
class LocationModel extends Model
{
    protected $table = 'locations';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'name',
        'country',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];
}
