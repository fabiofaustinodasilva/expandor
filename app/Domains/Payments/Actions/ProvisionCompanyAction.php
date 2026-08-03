<?php

namespace App\Domains\Payments\Actions;

use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Branding\Models\Brand;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Payments\DTOs\ProvisionedCompany;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisionCompanyAction
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    public function execute(CheckoutSession $checkout, Customer $customer): ProvisionedCompany
    {
        $adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
        $plainPassword = Str::password(12);

        return DB::transaction(function () use ($checkout, $customer, $adminRole, $plainPassword) {
            $company = Company::query()->create([
                'name' => $checkout->company_name,
                'legal_name' => $checkout->company_name,
                'document' => $checkout->buyer_document,
                'email' => $checkout->buyer_email,
                'phone' => $checkout->buyer_phone,
                'status' => Company::STATUS_ACTIVE,
                'is_system' => false,
            ]);

            $trialDays = (int) config('payments.trial_days', 0);
            $isTrial = $trialDays > 0;

            $subscription = Subscription::query()->withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'plan_id' => $checkout->plan_id,
                'status' => $isTrial ? Subscription::STATUS_TRIAL : Subscription::STATUS_ACTIVE,
                'starts_at' => now(),
                'ends_at' => null,
                'trial_ends_at' => $isTrial ? now()->addDays($trialDays) : null,
                'gateway' => $checkout->gateway,
                'billing_cycle' => $checkout->billing_cycle?->value ?? 'monthly',
                'next_billing_at' => $isTrial
                    ? now()->addDays($trialDays)
                    : ($checkout->billing_cycle?->value === 'yearly' ? now()->addYear() : now()->addMonth()),
            ]);

            $previousCompany = $this->tenant->company();
            $previousUser = $this->tenant->user();
            $this->tenant->set($company);

            try {
                Brand::query()->create([
                    'company_id' => $company->id,
                    'system_name' => config('app.name', 'Expandor'),
                    'display_name' => $company->name,
                    'theme' => BrandTheme::Dark->value,
                    'support_email' => $checkout->buyer_email,
                    'support_phone' => $checkout->buyer_phone,
                ]);

                foreach ([
                    'timezone' => 'America/Sao_Paulo',
                    'locale' => 'pt_BR',
                    'currency' => $checkout->currency ?: 'BRL',
                    'map_provider' => 'leaflet',
                    'theme' => 'dark',
                ] as $key => $value) {
                    CompanySetting::query()->create([
                        'company_id' => $company->id,
                        'key' => $key,
                        'value' => $value,
                    ]);
                }

                $administrator = User::query()->withoutGlobalScopes()->create([
                    'company_id' => $company->id,
                    'role_id' => $adminRole->id,
                    'name' => $checkout->buyer_name,
                    'email' => $checkout->buyer_email,
                    'phone' => $checkout->buyer_phone,
                    'password' => Hash::make($plainPassword),
                    'status' => User::STATUS_ACTIVE,
                    'is_platform_admin' => false,
                    'email_verified_at' => now(),
                ]);
            } finally {
                if ($previousCompany !== null) {
                    $this->tenant->set($previousCompany, $previousUser);
                } else {
                    $this->tenant->clear();
                }
            }

            $customer->forceFill(['company_id' => $company->id])->save();

            $checkout->forceFill([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'provisioned_at' => now(),
            ])->save();

            // Ensure subscription is available through relation for callers.
            unset($subscription);

            return new ProvisionedCompany(
                company: $company->fresh(),
                administrator: $administrator,
                customer: $customer->fresh(),
                checkout: $checkout->fresh(),
                plainPassword: $plainPassword,
            );
        });
    }
}
