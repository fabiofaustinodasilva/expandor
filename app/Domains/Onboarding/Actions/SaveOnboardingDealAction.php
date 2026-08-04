<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\CRM\Enums\LeadSource;
use App\Domains\CRM\Enums\LeadStatus;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Models\PipelineStage;
use App\Domains\CRM\Services\LeadService;
use App\Domains\CRM\Services\OpportunityService;
use App\Domains\CRM\Services\PipelineService;
use App\Domains\Onboarding\Events\OnboardingDealCreated;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use App\Domains\Sales\Products\Models\Product;
use Illuminate\Support\Facades\DB;

class SaveOnboardingDealAction
{
    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
        protected LeadService $leads,
        protected OpportunityService $opportunities,
        protected PipelineService $pipeline,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Company $company, array $data, ?User $actor = null): Opportunity
    {
        return DB::transaction(function () use ($company, $data, $actor) {
            $this->pipeline->ensureDefaultStages();

            $customerName = trim((string) $data['customer_name']);
            $email = filled($data['customer_email'] ?? null) ? strtolower(trim((string) $data['customer_email'])) : null;
            $phone = filled($data['customer_phone'] ?? null) ? trim((string) $data['customer_phone']) : null;
            $leadId = filled($data['lead_id'] ?? null) ? (int) $data['lead_id'] : null;

            if ($leadId > 0) {
                $lead = Lead::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->where('id', $leadId)
                    ->first();
                $lead ??= $this->resolveLead($company, $customerName, $email, $phone, $actor);
            } else {
                $lead = $this->resolveLead($company, $customerName, $email, $phone, $actor);
            }

            $productName = trim((string) $data['product_name']);
            $amount = (float) ($data['amount'] ?? 0);
            $product = Product::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'name' => $productName,
                ],
                [
                    'description' => 'Produto/serviço criado no onboarding',
                    'price' => $amount,
                    'commission_amount' => 0,
                    'stock_control' => false,
                    'stock_quantity' => 0,
                    'minimum_stock' => 0,
                    'status' => Product::STATUS_ACTIVE,
                    'is_demo' => false,
                ]
            );

            $statusSlug = (string) ($data['status'] ?? 'novo');
            $stage = PipelineStage::query()
                ->withoutGlobalScopes()
                ->where('company_id', $company->id)
                ->where('slug', $statusSlug)
                ->first()
                ?? PipelineStage::query()
                    ->withoutGlobalScopes()
                    ->where('company_id', $company->id)
                    ->where('slug', 'novo')
                    ->first();

            $existing = $this->saasOnboarding->findOnboardingDeal($company);
            $notes = '[Onboarding] Primeiro negócio · Produto: '.$product->name
                .' · Atividade inicial: contato de ativação registrado em '.now()->toDateTimeString();

            if ($existing !== null) {
                $opportunity = $this->opportunities->update($existing, [
                    'title' => $data['title'] ?? ('Primeiro negócio — '.$lead->name),
                    'amount' => $amount,
                    'probability' => $data['probability'] ?? 20,
                    'owner_id' => $actor?->id ?? $existing->owner_id,
                    'pipeline_stage_id' => $stage?->id ?? $existing->pipeline_stage_id,
                    'notes' => $notes,
                ], $actor);
            } else {
                $opportunity = $this->opportunities->create([
                    'lead_id' => $lead->id,
                    'title' => $data['title'] ?? ('Primeiro negócio — '.$lead->name),
                    'amount' => $amount,
                    'probability' => 20,
                    'owner_id' => $actor?->id,
                    'pipeline_stage_id' => $stage?->id,
                    'notes' => $notes,
                ], $actor);
            }

            $company = $this->saasOnboarding->advanceTo($company, SaasOnboardingService::STEP_BRANDING);
            OnboardingDealCreated::dispatch($company, $actor, [
                'opportunity_id' => $opportunity->id,
                'lead_id' => $lead->id,
                'product_id' => $product->id,
                'amount' => $amount,
                'status' => $statusSlug,
                'activity' => 'contato_ativacao',
            ]);

            return $opportunity;
        });
    }

    protected function resolveLead(
        Company $company,
        string $name,
        ?string $email,
        ?string $phone,
        ?User $actor,
    ): \App\Domains\CRM\Models\Lead {
        $existing = $this->saasOnboarding->findExistingOnboardingLead($company, $email, $phone);
        if ($existing !== null) {
            return $this->leads->update($existing, [
                'name' => $name !== '' ? $name : $existing->name,
                'email' => $email ?? $existing->email,
                'phone' => $phone ?? $existing->phone,
                'source' => LeadSource::MANUAL->value,
                'status' => $existing->status->value,
                'notes' => $existing->notes,
                'assigned_to' => $existing->assigned_to ?? $actor?->id,
            ], $actor);
        }

        return $this->leads->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'source' => LeadSource::MANUAL->value,
            'status' => LeadStatus::NEW->value,
            'notes' => '[Onboarding] Cliente do primeiro negócio',
            'assigned_to' => $actor?->id,
        ], $actor);
    }
}
