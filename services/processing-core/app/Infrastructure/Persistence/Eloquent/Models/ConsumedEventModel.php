<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model za tabelu consumed_events.
 *
 * Ovaj model služi infrastrukturnom sloju za evidenciju obrađenih eventa
 * i implementaciju idempotency mehanizma.
 */
class ConsumedEventModel extends Model
{
    protected $table = 'consumed_events';

    protected $fillable = [
        'event_id',
        'event_name',
        'consumed_at',
    ];

    protected $casts = [
        'consumed_at' => 'datetime',
    ];
}
