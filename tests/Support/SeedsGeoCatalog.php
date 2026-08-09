<?php

namespace Tests\Support;

use App\Domains\Geo\Models\GeoMunicipality;
use App\Domains\Geo\Models\GeoState;

trait SeedsGeoCatalog
{
    /**
     * Catálogo mínimo para testes (sem carregar 5k municípios).
     *
     * @return array{0: GeoState, 1: GeoMunicipality, 2: GeoMunicipality}
     */
    protected function seedMiniGeoCatalog(): array
    {
        $go = GeoState::query()->updateOrCreate(
            ['uf' => 'GO'],
            ['ibge_id' => 52, 'name' => 'Goiás']
        );
        $sp = GeoState::query()->updateOrCreate(
            ['uf' => 'SP'],
            ['ibge_id' => 35, 'name' => 'São Paulo']
        );

        $bomJardim = GeoMunicipality::query()->updateOrCreate(
            ['ibge_code' => '5203100'],
            ['geo_state_id' => $go->id, 'name' => 'Bom Jardim de Goiás']
        );
        $goiania = GeoMunicipality::query()->updateOrCreate(
            ['ibge_code' => '5208707'],
            ['geo_state_id' => $go->id, 'name' => 'Goiânia']
        );
        GeoMunicipality::query()->updateOrCreate(
            ['ibge_code' => '3550308'],
            ['geo_state_id' => $sp->id, 'name' => 'São Paulo']
        );

        return [$go, $bomJardim, $goiania];
    }
}
