<?php

namespace App\Domains\CRM\Services;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\OpportunityStatus;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Models\PipelineStage;
use App\Domains\CRM\Repositories\OpportunityRepository;
use App\Domains\CRM\Repositories\PipelineRepository;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpportunityService
{
    public function __construct(
        protected OpportunityRepository $repository,
        protected PipelineRepository $pipeline,
        protected PipelineService $pipelineService,
        protected SecurityService $security,
        protected CommissionService $commissions,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Opportunity
    {
        $this->pipelineService->ensureDefaultStages();

        $stageId = $data['pipeline_stage_id'] ?? $this->pipeline->firstOpenStage()?->id;
        if (! $stageId) {
            throw ValidationException::withMessages([
                'pipeline_stage_id' => ['Nenhum estágio de pipeline disponível.'],
            ]);
        }

        $opportunity = Opportunity::query()->create([
            'lead_id' => $data['lead_id'] ?? null,
            'title' => $data['title'],
            'pipeline_stage_id' => $stageId,
            'amount' => $data['amount'] ?? 0,
            'probability' => $data['probability'] ?? 10,
            'owner_id' => $data['owner_id'] ?? $actor?->id,
            'campaign_id' => $data['campaign_id'] ?? null,
            'property_id' => $data['property_id'] ?? null,
            'resident_id' => $data['resident_id'] ?? null,
            'visit_id' => $data['visit_id'] ?? null,
            'status' => OpportunityStatus::OPEN,
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if ($actor) {
            $this->security->recordAudit(
                action: 'crm.opportunity.created',
                user: $actor,
                auditable: $opportunity,
                newValues: [
                    'title' => $opportunity->title,
                    'amount' => $opportunity->amount,
                    'stage_id' => $opportunity->pipeline_stage_id,
                ],
                companyId: $opportunity->company_id,
            );
        }

        return $opportunity->fresh(['stage', 'owner', 'lead']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromLead(Lead $lead, array $data = [], ?User $actor = null): Opportunity
    {
        return $this->create([
            'lead_id' => $lead->id,
            'title' => $data['title'] ?? ('Oportunidade — '.$lead->name),
            'amount' => $data['amount'] ?? 0,
            'probability' => $data['probability'] ?? 20,
            'owner_id' => $data['owner_id'] ?? $lead->assigned_to,
            'campaign_id' => $lead->campaign_id,
            'property_id' => $lead->property_id,
            'resident_id' => $lead->resident_id,
            'visit_id' => $lead->visit_id,
            'expected_close_date' => $data['expected_close_date'] ?? now()->addDays(30)->toDateString(),
            'notes' => $data['notes'] ?? $lead->notes,
            'pipeline_stage_id' => $data['pipeline_stage_id'] ?? null,
        ], $actor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Opportunity $opportunity, array $data, ?User $actor = null): Opportunity
    {
        if ($opportunity->status !== OpportunityStatus::OPEN) {
            throw ValidationException::withMessages([
                'opportunity' => ['Somente oportunidades abertas podem ser editadas.'],
            ]);
        }

        $old = ['title' => $opportunity->title, 'amount' => $opportunity->amount];

        $opportunity->update([
            'title' => $data['title'],
            'amount' => $data['amount'] ?? $opportunity->amount,
            'probability' => $data['probability'] ?? $opportunity->probability,
            'owner_id' => $data['owner_id'] ?? $opportunity->owner_id,
            'expected_close_date' => $data['expected_close_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'pipeline_stage_id' => $data['pipeline_stage_id'] ?? $opportunity->pipeline_stage_id,
        ]);

        if ($actor) {
            $this->security->recordAudit(
                action: 'crm.opportunity.updated',
                user: $actor,
                auditable: $opportunity,
                oldValues: $old,
                newValues: ['title' => $opportunity->title, 'amount' => $opportunity->amount],
                companyId: $opportunity->company_id,
            );
        }

        return $opportunity->fresh(['stage', 'owner', 'lead']);
    }

    public function moveStage(Opportunity $opportunity, PipelineStage $stage, ?User $actor = null): Opportunity
    {
        if ($opportunity->status !== OpportunityStatus::OPEN && ! $stage->is_won && ! $stage->is_lost) {
            throw ValidationException::withMessages([
                'pipeline_stage_id' => ['Oportunidade fechada não pode mudar de estágio aberto.'],
            ]);
        }

        if ($opportunity->company_id !== $stage->company_id) {
            abort(404);
        }

        $oldStage = $opportunity->pipeline_stage_id;

        if ($stage->is_won) {
            return $this->win($opportunity, $actor);
        }

        if ($stage->is_lost) {
            return $this->lose($opportunity, ['lost_reason' => 'Movido para estágio perdido'], $actor);
        }

        $opportunity->forceFill([
            'pipeline_stage_id' => $stage->id,
            'status' => OpportunityStatus::OPEN,
        ])->save();

        if ($actor) {
            $this->security->recordAudit(
                action: 'crm.opportunity.stage_moved',
                user: $actor,
                auditable: $opportunity,
                oldValues: ['pipeline_stage_id' => $oldStage],
                newValues: ['pipeline_stage_id' => $stage->id],
                companyId: $opportunity->company_id,
            );
        }

        return $opportunity->fresh(['stage', 'owner']);
    }

    public function win(Opportunity $opportunity, ?User $actor = null): Opportunity
    {
        $this->pipelineService->ensureDefaultStages();
        $wonStage = $this->pipeline->wonStage();

        if (! $wonStage) {
            throw ValidationException::withMessages([
                'opportunity' => ['Estágio de ganho não configurado.'],
            ]);
        }

        return DB::transaction(function () use ($opportunity, $wonStage, $actor) {
            $old = ['status' => $opportunity->status->value, 'stage_id' => $opportunity->pipeline_stage_id];

            $opportunity->forceFill([
                'status' => OpportunityStatus::WON,
                'pipeline_stage_id' => $wonStage->id,
                'probability' => 100,
                'closed_at' => now(),
                'lost_reason' => null,
            ])->save();

            $this->commissions->prepareForWonOpportunity($opportunity->fresh(), $actor);

            if ($actor) {
                $this->security->recordAudit(
                    action: 'crm.opportunity.won',
                    user: $actor,
                    auditable: $opportunity,
                    oldValues: $old,
                    newValues: ['status' => OpportunityStatus::WON->value, 'amount' => $opportunity->amount],
                    companyId: $opportunity->company_id,
                );
            }

            return $opportunity->fresh(['stage', 'owner', 'commissionEntries']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function lose(Opportunity $opportunity, array $data = [], ?User $actor = null): Opportunity
    {
        $this->pipelineService->ensureDefaultStages();
        $lostStage = $this->pipeline->lostStage();

        if (! $lostStage) {
            throw ValidationException::withMessages([
                'opportunity' => ['Estágio de perda não configurado.'],
            ]);
        }

        $old = ['status' => $opportunity->status->value];

        $opportunity->forceFill([
            'status' => OpportunityStatus::LOST,
            'pipeline_stage_id' => $lostStage->id,
            'probability' => 0,
            'closed_at' => now(),
            'lost_reason' => $data['lost_reason'] ?? null,
        ])->save();

        if ($actor) {
            $this->security->recordAudit(
                action: 'crm.opportunity.lost',
                user: $actor,
                auditable: $opportunity,
                oldValues: $old,
                newValues: [
                    'status' => OpportunityStatus::LOST->value,
                    'lost_reason' => $opportunity->lost_reason,
                ],
                companyId: $opportunity->company_id,
            );
        }

        return $opportunity->fresh(['stage', 'owner']);
    }
}
