<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Weather\Contracts\LocationRepository;
use App\Domain\Weather\Location;
use App\Infrastructure\Persistence\Eloquent\Models\LocationModel;

/**
 * Eloquent implementacija repository-ja za lokacije.
 *
 * Ovde prevodimo infrastrukturni Eloquent model u domain entitet.
 * Time application sloj i domen ostaju izolovani od ORM detalja.
 */
class EloquentLocationRepository implements LocationRepository
{
    /**
     * Vraća sve aktivne lokacije kao domain entitete.
     *
     * @return Location[]
     */
    public function getActiveLocations(): array
    {
        $rows = LocationModel::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return $rows->map(function (LocationModel $row) {
            return new Location(
                id: (string) $row->id,
                name: (string) $row->name,
                country: (string) $row->country,
                latitude: (float) $row->latitude,
                longitude: (float) $row->longitude,
                isActive: (bool) $row->is_active,
            );
        })->all();
    }
}
