<?php

namespace App\Domains\CRM\Services;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\CommissionStatus;
use App\Domains\CRM\Models\CommissionEntry;
use App\Domains\CRM\Models\CommissionRule;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Repositories\CrmMetricsRepository;
use App\Domains\Security\Services\SecurityService;

class CommissionService
{
    public function __construct(
        protected CrmMetricsRepository $repository,
        protected SecurityService $security,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRule(array $data, ?User $actor = null): CommissionRule
    {
        $rule = CommissionRule::query()->create([
            'name' => $data['name'],
            'percent' => $data['percent'],
            'min_amount' => $data['min_amount'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        if ($actor) {
            $this->security->recordAudit(
                action: 'crm.commission_rule.created',
                user: $actor,
                auditable: $rule,
                newValues: ['name' => $rule->name, 'percent' => $rule->percent],
                companyId: $rule->company_id,
            );
        }

        return $rule;
    }

    public function prepareForWonOpportunity(Opportunity $opportunity, ?User $actor = null): ?CommissionEntry
    {
        if (! $opportunity->owner_id) {
            return null;
        }

        $existing = CommissionEntry::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('user_id', $opportunity->owner_id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $rule = $this->repository->activeCommissionRule();
        $percent = $rule ? (float) $rule->percent : 0.0;
        $base = (float) $opportunity->amount;

        if ($rule && $rule->min_amount !== null && $base < (float) $rule->min_amount) {
            $percent = 0.0;
        }

        $entry = CommissionEntry::query()->create([
            'opportunity_id' => $opportunity->id,
            'user_id' => $opportunity->owner_id,
            'commission_rule_id' => $rule?->id,
            'base_amount' => $base,
            'percent' => $percent,
            'commission_amount' => round($base * ($percent / 100), 2),
            'status' => CommissionStatus::PENDING,
            'calculated_at' => now(),
        ]);

        if ($actor) {
            $this->security->recordAudit(
                action: 'crm.commission.prepared',
                user: $actor,
                auditable: $entry,
                newValues: [
                    'opportunity_id' => $opportunity->id,
                    'commission_amount' => $entry->commission_amount,
                    'percent' => $percent,
                ],
                companyId: $entry->company_id,
            );
        }

        return $entry;
    }
}
