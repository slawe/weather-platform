<?php

namespace App\Infrastructure\Messaging\Serialization;

use App\Application\Messaging\DTO\IncomingMessage;
use App\Application\Messaging\DTO\MessageMetadata;
use RuntimeException;

/**
 * Deserializer koji raw JSON poruku prevodi u canonical IncomingMessage DTO.
 *
 * Ova klasa poznaje canonical integration event envelope i prevodi ga
 *  u objekat sa kojim ostatak application sloja može uredno da radi.
 */
final class IncomingMessageDeserializer
{
    /**
     * Pretvara raw JSON telo poruke u IncomingMessage objekat.
     *
     * @param array<string, mixed> $headers
     */
    public function deserialize(string $body, array $headers = []): IncomingMessage
    {
        $decoded = $this->decodeBody($body);

        return new IncomingMessage(
            metadata: $this->buildMetadata($decoded, $headers),
            payload: $this->extractPayload($decoded),
        );
    }

    /**
     * Pretvara JSON string u niz podataka.
     *
     * @return array<string, mixed>
     */
    private function decodeBody(string $body): array
    {
        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (\JsonException $e) {
            throw new RuntimeException('Neuspešna deserializacija incoming poruke.', 0, $e);
        }
    }

    /**
     * Kreira metadata objekat iz envelope podataka.
     *
     * @param array<string, mixed> $decoded
     * @param array<string, mixed> $headers
     */
    private function buildMetadata(array $decoded, array $headers): MessageMetadata
    {
        $eventId = (string) ($decoded['event_id'] ?? '');
        $eventName = (string) ($decoded['event_name'] ?? '');

        if ($eventId === '' || $eventName === '') {
            throw new RuntimeException('Incoming poruka nema obavezne metadata podatke.');
        }

        return new MessageMetadata(
            eventId: $eventId,
            eventName: $eventName,
            eventVersion: (int) ($decoded['event_version'] ?? 1),
            occurredAt: (string) ($decoded['occurred_at'] ?? ''),
            producer: (string) ($decoded['producer'] ?? ''),
            headers: $headers,
        );
    }

    /**
     * Izdvaja i validira payload deo incoming poruke.
     *
     * @param array<string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function extractPayload(array $decoded): array
    {
        $payload = $decoded['payload'] ?? null;

        if (!is_array($payload)) {
            throw new RuntimeException('Incoming poruka nema validan payload.');
        }

        return $payload;
    }
}
