<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model za tabelu outbox_messages.
 *
 * Ovaj model predstavlja persistirane integration poruke koje čekaju publish
 * ili su već obrađene od strane outbox publisher-a.
 */
class OutboxMessageModel extends Model
{
    protected $table = 'outbox_messages';
    protected $fillable = [
        'event_id',
        'event_name',
        'event_version',
        'routing_key',
        'deduplication_key',
        'payload',
        'headers',
        'status',
        'attempts',
        'available_at',
        'published_at',
        'last_error',
    ];
}
