<?php

namespace App\Domains\Sales\Territory\Services;

use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;

class TerritoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createCity(array $data): City
    {
        return City::query()->create([
            'name' => $data['name'],
            'state' => strtoupper($data['state']),
            'ibge_code' => $data['ibge_code'] ?? null,
            'active' => array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)
                : true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCity(City $city, array $data): City
    {
        $city->update([
            'name' => $data['name'],
            'state' => strtoupper($data['state']),
            'ibge_code' => $data['ibge_code'] ?? null,
            'active' => array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)
                : $city->active,
        ]);

        return $city->refresh();
    }

    public function toggleCity(City $city): City
    {
        $city->update(['active' => ! $city->active]);

        return $city->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createSector(array $data): Sector
    {
        /** @var City $city */
        $city = City::query()->findOrFail($data['city_id']);

        return Sector::query()->create([
            'city_id' => $city->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)
                : true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSector(Sector $sector, array $data): Sector
    {
        /** @var City $city */
        $city = City::query()->findOrFail($data['city_id']);

        $sector->update([
            'city_id' => $city->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'active' => array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)
                : $sector->active,
        ]);

        return $sector->refresh();
    }

    public function toggleSector(Sector $sector): Sector
    {
        $sector->update(['active' => ! $sector->active]);

        return $sector->refresh();
    }
}
