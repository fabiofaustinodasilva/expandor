<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Mantido por compatibilidade com testes e DatabaseSeeder.
 * Delega ao MarketplaceDefaultSeeder (fonte única de conteúdo padrão).
 */
class MarketplaceCmsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(MarketplaceDefaultSeeder::class);
    }
}
