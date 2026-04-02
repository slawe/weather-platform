<?php

namespace App\Infrastructure\Messaging\Serialization;

use JsonException;
use RuntimeException;

/**
 * Serializer koji od payload niza pravi JSON string spreman za slanje na broker.
 *
 * Iako je ovo trenutno jednostavan json_encode, izdvajamo ga u posebnu klasu
 * da bismo kasnije lakše menjali format, validaciju ili dodatnu obradu.
 */
final class IntegrationEventSerializer
{
    /**
     * Pretvara payload u JSON string.
     *
     * @param array<string, mixed> $payload
     */
    public function serialize(array $payload): string
    {
        try {
            return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Neuspešna serializacija integration event payload-a.', 0, $e);
        }
    }
}
