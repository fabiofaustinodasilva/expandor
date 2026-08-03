<?php

namespace App\Domains\Sales\Territory\Actions;

use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Sales\Territory\Services\TerritoryService;

class ToggleSectorStatusAction
{
    public function __construct(
        protected TerritoryService $territory
    ) {}

    public function execute(Sector $sector): Sector
    {
        return $this->territory->toggleSector($sector);
    }
}
