<?php

namespace App\Domains\Sales\Residents\Actions;

use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Residents\Services\ResidentService;

class ChangeResidentStatusAction
{
    public function __construct(
        protected ResidentService $residents
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Resident $resident, array $data): Resident
    {
        return $this->residents->changeStatus($resident, $data);
    }
}
