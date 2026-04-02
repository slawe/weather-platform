<?php

namespace App\Support\Messaging;

use InvalidArgumentException;

/**
 * Ova klasa centralizuje mapiranje između naziva eventa i RabbitMQ routing key-a.
 *
 * Time izbegavamo hardkodovanje routing key stringova po raznim delovima sistema.
 * Ako se naming konvencija promeni, menjamo na jednom mestu.
 */
final class RoutingKeyResolver
{
    /**
     * Vraća routing key za zadati event name.
     */
    public function resolve(string $eventName): string
    {
        $routingKeys = config('rabbitmq.routing_keys', []);

        $mapping = [
            'weather.snapshot.fetched' => $routingKeys['weather_snapshot_fetched'] ?? null,
        ];

        $routingKey = $mapping[$eventName] ?? null;

        if ($routingKey === null) {
            throw new InvalidArgumentException("Routing key nije definisan za event: {$eventName}");
        }

        return $routingKey;
    }
}
