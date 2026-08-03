<?php

namespace App\Domains\Platform\DTOs;

readonly class CompanyHealthDTO
{
    /**
     * @param  array<string, mixed>  $factors
     */
    public function __construct(
        public int $companyId,
        public int $score,
        public string $riskLevel,
        public array $factors,
        public string $calculatedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'score' => $this->score,
            'risk_level' => $this->riskLevel,
            'factors' => $this->factors,
            'calculated_at' => $this->calculatedAt,
        ];
    }
}
