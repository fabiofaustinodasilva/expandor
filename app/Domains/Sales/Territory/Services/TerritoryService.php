<?php

namespace App\Domains\Sales\Territory\Services;

use App\Domains\Geo\Models\GeoMunicipality;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

class TerritoryService
{
    /**
     * Materializa City operacional do tenant a partir do município do catálogo global.
     * Preferência: company_id + geo_municipality_id; fallback legado name+state / ibge_code.
     */
    public function upsertCityFromCatalog(GeoMunicipality|int $municipality): City
    {
        $municipality = $municipality instanceof GeoMunicipality
            ? $municipality->loadMissing('state')
            : GeoMunicipality::query()->with('state')->findOrFail($municipality);

        $companyId = (int) (app(TenantContext::class)->id() ?? 0);
        if ($companyId <= 0) {
            throw ValidationException::withMessages([
                'geo_municipality_id' => 'Empresa não identificada para materializar a cidade.',
            ]);
        }

        $uf = strtoupper((string) $municipality->state?->uf);
        $name = trim((string) $municipality->name);
        $ibge = (string) $municipality->ibge_code;

        $query = City::query()->withoutGlobalScopes()->where('company_id', $companyId);

        $existing = (clone $query)->where('geo_municipality_id', $municipality->id)->first();

        if ($existing === null && $ibge !== '') {
            $existing = (clone $query)
                ->whereNull('geo_municipality_id')
                ->where('ibge_code', $ibge)
                ->first();
        }

        if ($existing === null) {
            $existing = (clone $query)
                ->whereNull('geo_municipality_id')
                ->where('name', $name)
                ->where('state', $uf)
                ->first();
        }

        if ($existing !== null) {
            $existing->fill([
                'name' => $name,
                'state' => $uf,
                'ibge_code' => $ibge,
                'geo_municipality_id' => $municipality->id,
                'active' => true,
            ]);
            $existing->save();

            return $existing->refresh();
        }

        return City::query()->withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'name' => $name,
            'state' => $uf,
            'ibge_code' => $ibge,
            'geo_municipality_id' => $municipality->id,
            'active' => true,
        ]);
    }

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
            'geo_municipality_id' => $data['geo_municipality_id'] ?? null,
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

        if ($this->isReservedWholeCitySectorName($name)) {
            throw ValidationException::withMessages([
                'name' => 'Use "Toda a cidade" na campanha em vez de criar uma área com este nome.',
            ]);
        }

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

    public function isReservedWholeCitySectorName(string $name): bool
    {
        $normalized = mb_strtolower(trim($name));

        return in_array($normalized, [
            'todos',
            'todos os setores',
            'toda a cidade',
            'todas as areas',
            'todas as áreas',
        ], true);
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
