<?php

namespace Database\Seeders;

use App\Domains\Geo\Models\GeoMunicipality;
use App\Domains\Geo\Models\GeoState;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $statesPath = database_path('data/geo/states.json');
        $munPath = database_path('data/geo/municipalities.json');

        if (! is_file($statesPath) || ! is_file($munPath)) {
            throw new \RuntimeException(
                'Catálogo geo ausente. Rode: php scripts/fetch-ibge-geo-catalog.php'
            );
        }

        /** @var list<array{id:int,uf:string,name:string}> $states */
        $states = json_decode((string) file_get_contents($statesPath), true, 512, JSON_THROW_ON_ERROR);
        /** @var list<array{ibge_code:string,name:string,uf:string}> $municipalities */
        $municipalities = json_decode((string) file_get_contents($munPath), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($states, $municipalities): void {
            $ufToId = [];

            foreach ($states as $state) {
                $row = GeoState::query()->updateOrCreate(
                    ['uf' => strtoupper($state['uf'])],
                    [
                        'ibge_id' => (int) $state['id'],
                        'name' => $state['name'],
                    ]
                );
                $ufToId[strtoupper($state['uf'])] = $row->id;
            }

            $now = now();
            $buffer = [];

            foreach ($municipalities as $mun) {
                $uf = strtoupper($mun['uf']);
                $stateId = $ufToId[$uf] ?? null;
                if ($stateId === null) {
                    continue;
                }

                $buffer[] = [
                    'geo_state_id' => $stateId,
                    'ibge_code' => (string) $mun['ibge_code'],
                    'name' => $mun['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($buffer) >= 500) {
                    $this->upsertMunicipalities($buffer);
                    $buffer = [];
                }
            }

            if ($buffer !== []) {
                $this->upsertMunicipalities($buffer);
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function upsertMunicipalities(array $rows): void
    {
        GeoMunicipality::query()->upsert(
            $rows,
            ['ibge_code'],
            ['geo_state_id', 'name', 'updated_at']
        );
    }
}
