<?php

namespace App\Domain\Weather;

/**
 * Domain entitet koji predstavlja lokaciju za koju prikupljamo vremenske podatke.
 */
final readonly class Location
{
    /**
     * Kreira novu lokaciju.
     *
     * @param string $id
     * @param string $name
     * @param string $country
     * @param float $latitude
     * @param float $longitude
     * @param bool $isActive
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $country,
        public float $latitude,
        public float $longitude,
        public bool $isActive = true,
    ) {
    }

    /**
     * Vraća lokaciju kao niz podataka.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_active' => $this->isActive,
        ];
    }
}
