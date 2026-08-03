<?php

namespace App\Domains\Sales\Territory\Actions;

use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Services\TerritoryService;

class ToggleCityStatusAction
{
    public function __construct(
        protected TerritoryService $territory
    ) {}

    public function execute(City $city): City
    {
        return $this->territory->toggleCity($city);
    }
}
