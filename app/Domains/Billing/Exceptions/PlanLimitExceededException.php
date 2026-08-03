<?php

namespace App\Domains\Billing\Exceptions;

use App\Domains\Billing\DTOs\LimitCheckResult;
use App\Domains\Billing\Enums\UsageMetric;
use RuntimeException;

class PlanLimitExceededException extends RuntimeException
{
    public function __construct(
        public readonly LimitCheckResult $result,
    ) {
        $metric = $result->metric->label();
        $limit = $result->limit ?? 0;

        parent::__construct(
            "Seu plano atual permite {$limit} {$metric}. Faça upgrade para continuar."
        );
    }

    public function metric(): UsageMetric
    {
        return $this->result->metric;
    }
}
