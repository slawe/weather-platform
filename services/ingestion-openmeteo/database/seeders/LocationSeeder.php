<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\LocationModel;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

/**
 * Seeder koji ubacuje početne lokacije koje ingestion servis prati.
 *
 * Za početak koristimo nekoliko gradova u Srbiji da bismo mogli brzo da
 * testiramo ceo tok bez ručnog unosa podataka u bazu.
 */
class LocationSeeder extends Seeder
{
    /**
     * Pokreće ubacivanje početnih lokacija.
     */
    public function run(): void
    {
        $locations = config('weather.default_locations', []);
        $configuredLocationIds = [];

        foreach ($locations as $location) {
            $locationModel = LocationModel::query()->firstOrNew([
                'name' => $location['name'],
                'country' => $location['country'],
            ]);

            $locationModel->id = $location['id'] ?? ($locationModel->id ?: Uuid::uuid7()->toString());
            $configuredLocationIds[] = $locationModel->id;

            $locationModel->latitude = $location['latitude'];
            $locationModel->longitude = $location['longitude'];
            $locationModel->is_active = true;
            $locationModel->save();
        }

        if ($configuredLocationIds !== []) {
            LocationModel::query()
                ->whereNotIn('id', $configuredLocationIds)
                ->update(['is_active' => false]);
        }
    }
}
