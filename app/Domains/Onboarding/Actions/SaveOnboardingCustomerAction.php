<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\LeadSource;
use App\Domains\CRM\Enums\LeadStatus;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Services\LeadService;
use App\Domains\Onboarding\Events\OnboardingCustomerCreated;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use App\Domains\Sales\Territory\Services\TerritoryService;
use Illuminate\Support\Facades\DB;

class SaveOnboardingCustomerAction
{
    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
        protected LeadService $leads,
        protected TerritoryService $territory,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Company $company, array $data, ?User $actor = null): Lead
    {
        return DB::transaction(function () use ($company, $data, $actor) {
            $email = filled($data['email'] ?? null) ? strtolower(trim((string) $data['email'])) : null;
            $phone = filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null;
            $cityName = trim((string) ($data['city'] ?? ''));
            $state = strtoupper(trim((string) ($data['state'] ?? 'SP')));

            if ($cityName !== '') {
                $this->territory->upsertCity([
                    'company_id' => $company->id,
                    'name' => $cityName,
                    'state' => $state !== '' ? $state : 'SP',
                    'active' => true,
                ]);
            }

            $notes = $cityName !== ''
                ? '[Onboarding] Cidade: '.$cityName.($state !== '' ? '/'.$state : '')
                : '[Onboarding] Cliente inicial';

            $existing = $this->saasOnboarding->findExistingOnboardingLead($company, $email, $phone);

            if ($existing !== null) {
                $lead = $this->leads->update($existing, [
                    'name' => $data['name'],
                    'email' => $email,
                    'phone' => $phone,
                    'source' => LeadSource::MANUAL->value,
                    'status' => $existing->status->value,
                    'notes' => $notes,
                    'assigned_to' => $existing->assigned_to ?? $actor?->id,
                ], $actor);
            } else {
                $lead = $this->leads->create([
                    'name' => $data['name'],
                    'email' => $email,
                    'phone' => $phone,
                    'source' => LeadSource::MANUAL->value,
                    'status' => LeadStatus::NEW->value,
                    'notes' => $notes,
                    'assigned_to' => $actor?->id,
                ], $actor);
            }

            $company = $this->saasOnboarding->advanceTo($company, SaasOnboardingService::STEP_SALES_SETUP);
            OnboardingCustomerCreated::dispatch($company, $actor, [
                'lead_id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'reused' => $existing !== null,
            ]);

            return $lead;
        });
    }
}
