<?php

namespace App\Domains\Sales\Territory\Services;

use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Tenancy\TenantContext;

class TerritoryService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createCity(array $data): City
    {
        return $this->upsertCity($data);
    }

    /**
     * Cria ou reutiliza cidade pela unique (company_id, name, state).
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertCity(array $data): City
    {
        $name = trim((string) $data['name']);
        $state = strtoupper(trim((string) $data['state']));
        $companyId = (int) (app(TenantContext::class)->id() ?? $data['company_id'] ?? 0);

        $attributes = [
            'ibge_code' => $data['ibge_code'] ?? null,
            'active' => array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)
                : true,
        ];

        if ($companyId > 0) {
            return City::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'name' => $name,
                    'state' => $state,
                ],
                $attributes,
            );
        }

        return City::query()->updateOrCreate(
            [
                'name' => $name,
                'state' => $state,
            ],
            $attributes,
        );
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
        return $this->upsertSector($data);
    }

    /**
     * Cria ou reutiliza setor pela unique (company_id, city_id, name).
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertSector(array $data): Sector
    {
        /** @var City $city */
        $city = City::query()->findOrFail($data['city_id']);
        $name = trim((string) $data['name']);
        $companyId = (int) ($city->company_id ?? app(TenantContext::class)->id() ?? 0);

        $attributes = [
            'description' => $data['description'] ?? null,
            'active' => array_key_exists('active', $data)
                ? filter_var($data['active'], FILTER_VALIDATE_BOOLEAN)
                : true,
        ];

        if ($companyId > 0) {
            return Sector::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'city_id' => $city->id,
                    'name' => $name,
                ],
                $attributes,
            );
        }

        return Sector::query()->updateOrCreate(
            [
                'city_id' => $city->id,
                'name' => $name,
            ],
            $attributes,
        );
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
