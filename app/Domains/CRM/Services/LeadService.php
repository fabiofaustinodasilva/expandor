<?php

namespace App\Domains\CRM\Services;

use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\LeadSource;
use App\Domains\CRM\Enums\LeadStatus;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Repositories\LeadRepository;
use App\Domains\Security\Services\SecurityService;
use App\Domains\Visits\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadService
{
    public function __construct(
        protected LeadRepository $repository,
        protected SecurityService $security,
        protected OpportunityService $opportunities,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Lead
    {
        $lead = Lead::query()->create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => LeadSource::from($data['source'] ?? LeadSource::MANUAL->value),
            'status' => LeadStatus::from($data['status'] ?? LeadStatus::NEW->value),
            'assigned_to' => $data['assigned_to'] ?? $actor?->id,
            'campaign_id' => $data['campaign_id'] ?? null,
            'property_id' => $data['property_id'] ?? null,
            'resident_id' => $data['resident_id'] ?? null,
            'visit_id' => $data['visit_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'qualified_at' => ($data['status'] ?? null) === LeadStatus::QUALIFIED->value ? now() : null,
        ]);

        if ($actor) {
            $this->security->recordAudit(
                action: 'crm.lead.created',
                user: $actor,
                auditable: $lead,
                newValues: ['name' => $lead->name, 'status' => $lead->status->value],
                companyId: $lead->company_id,
            );
        }

        return $lead->fresh(['assignee', 'campaign']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lead $lead, array $data, ?User $actor = null): Lead
    {
        $old = ['status' => $lead->status->value, 'name' => $lead->name];

        $status = LeadStatus::from($data['status'] ?? $lead->status->value);

        $lead->update([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'source' => LeadSource::from($data['source'] ?? $lead->source->value),
            'status' => $status,
            'assigned_to' => $data['assigned_to'] ?? null,
            'campaign_id' => $data['campaign_id'] ?? null,
            'property_id' => $data['property_id'] ?? null,
            'resident_id' => $data['resident_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'qualified_at' => $status === LeadStatus::QUALIFIED ? ($lead->qualified_at ?? now()) : $lead->qualified_at,
        ]);

        if ($actor) {
            $this->security->recordAudit(
                action: 'crm.lead.updated',
                user: $actor,
                auditable: $lead,
                oldValues: $old,
                newValues: ['status' => $lead->status->value, 'name' => $lead->name],
                companyId: $lead->company_id,
            );
        }

        return $lead->fresh(['assignee', 'campaign']);
    }

    public function createFromVisit(Visit $visit, ?User $actor = null): Lead
    {
        $visit->loadMissing(['property.residents', 'campaign', 'user']);

        $resident = $visit->property?->residents?->first();

        return $this->create([
            'name' => $resident?->name ?? ('Lead visita #'.$visit->id),
            'phone' => $resident?->phone ?? null,
            'email' => $resident?->email ?? null,
            'source' => LeadSource::VISIT->value,
            'status' => LeadStatus::NEW->value,
            'assigned_to' => $visit->user_id,
            'campaign_id' => $visit->campaign_id,
            'property_id' => $visit->property_id,
            'resident_id' => $resident?->id,
            'visit_id' => $visit->id,
            'notes' => $visit->notes,
        ], $actor);
    }

    public function convert(Lead $lead, array $data = [], ?User $actor = null): Lead
    {
        if ($lead->status === LeadStatus::CONVERTED) {
            throw ValidationException::withMessages([
                'lead' => ['Este lead já foi convertido.'],
            ]);
        }

        if ($lead->status === LeadStatus::DISQUALIFIED) {
            throw ValidationException::withMessages([
                'lead' => ['Lead desqualificado não pode ser convertido.'],
            ]);
        }

        return DB::transaction(function () use ($lead, $data, $actor) {
            $opportunity = $this->opportunities->createFromLead($lead, $data, $actor);

            $lead->forceFill([
                'status' => LeadStatus::CONVERTED,
                'converted_at' => now(),
                'converted_opportunity_id' => $opportunity->id,
            ])->save();

            if ($actor) {
                $this->security->recordAudit(
                    action: 'crm.lead.converted',
                    user: $actor,
                    auditable: $lead,
                    newValues: [
                        'opportunity_id' => $opportunity->id,
                        'status' => LeadStatus::CONVERTED->value,
                    ],
                    companyId: $lead->company_id,
                );
            }

            return $lead->fresh(['convertedOpportunity']);
        });
    }
}
