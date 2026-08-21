<?php

/**
 * Seed visual QA scenarios for SaaS billing (local only).
 * Usage: php artisan tinker --execute="require 'database/scripts/seed_billing_visual_qa.php';"
 */

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Role;
use App\Domains\Company\Models\Subscription;
use App\Domains\Company\Models\User;
use App\Domains\Payments\Enums\InvoiceStatus;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Support\BillingSuspensionReasons;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$plan = Plan::query()->where('slug', 'pro')->first() ?? Plan::query()->where('price', '>', 0)->orderBy('price')->first();
if (! $plan) {
    echo "NO_PLAN\n";

    return;
}

$adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->firstOrFail();
$sellerRole = Role::query()->where('slug', Role::SELLER)->firstOrFail();
$managerRole = Role::query()->where('slug', Role::MANAGER)->firstOrFail();

$makeCompany = function (string $name, string $emailPrefix, array $companyAttrs, array $subAttrs, ?callable $invoiceFn = null) use ($plan, $adminRole, $sellerRole, $managerRole) {
    $company = Company::query()->updateOrCreate(
        ['email' => $emailPrefix.'@qa.expandor.local'],
        array_merge([
            'name' => $name,
            'legal_name' => $name,
            'document' => (string) random_int(10000000000000, 99999999999999),
            'status' => Company::STATUS_ACTIVE,
            'is_system' => false,
        ], $companyAttrs)
    );

    $started = now()->subMonthsNoOverflow(2);
    $sub = Subscription::query()->withoutGlobalScopes()->updateOrCreate(
        ['company_id' => $company->id],
        array_merge([
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'starts_at' => $started,
            'contract_started_at' => $started,
            'minimum_term_months' => 6,
            'minimum_term_ends_at' => $started->copy()->addMonthsNoOverflow(6),
            'billing_cycle' => 'monthly',
            'gateway' => 'mercadopago',
            'next_billing_at' => now()->addDays(20),
        ], $subAttrs)
    );

    $users = [
        ['email' => $emailPrefix.'.admin@qa.expandor.local', 'role' => $adminRole, 'name' => 'Admin '.$name],
        ['email' => $emailPrefix.'.seller@qa.expandor.local', 'role' => $sellerRole, 'name' => 'Seller '.$name],
        ['email' => $emailPrefix.'.manager@qa.expandor.local', 'role' => $managerRole, 'name' => 'Manager '.$name],
    ];

    foreach ($users as $u) {
        User::query()->withoutGlobalScopes()->updateOrCreate(
            ['email' => $u['email']],
            [
                'company_id' => $company->id,
                'role_id' => $u['role']->id,
                'name' => $u['name'],
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
    }

    Invoice::query()->withoutGlobalScopes()->where('company_id', $company->id)->delete();

    if ($invoiceFn) {
        $invoiceFn($company, $sub, $plan);
    }

    return $company;
};

$active = $makeCompany('QA Financeiro Ativo', 'qa.ativo', [], [], function ($company, $sub, $plan) {
    Invoice::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'subscription_id' => $sub->id,
        'plan_id' => $plan->id,
        'number' => 'INV-QA-ATIVO',
        'billing_period_key' => now()->format('Y-m').'-ativo',
        'status' => InvoiceStatus::Open,
        'amount_due' => $plan->price,
        'amount_paid' => 0,
        'currency' => 'BRL',
        'due_at' => now()->addDays(5)->startOfDay(),
        'gateway' => 'mercadopago',
    ]);
    Invoice::query()->withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'subscription_id' => $sub->id,
        'plan_id' => $plan->id,
        'number' => 'INV-QA-PAID',
        'billing_period_key' => now()->subMonth()->format('Y-m').'-paid',
        'status' => InvoiceStatus::Paid,
        'amount_due' => $plan->price,
        'amount_paid' => $plan->price,
        'currency' => 'BRL',
        'due_at' => now()->subMonth()->startOfDay(),
        'paid_at' => now()->subMonth()->addDays(2),
        'payment_method' => 'pix',
        'gateway' => 'mercadopago',
    ]);
});

$grace = $makeCompany(
    'QA Financeiro Tolerancia',
    'qa.grace',
    [],
    ['next_billing_at' => now()->subDays(3)],
    function ($company, $sub, $plan) {
        Invoice::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'subscription_id' => $sub->id,
            'plan_id' => $plan->id,
            'number' => 'INV-QA-GRACE',
            'billing_period_key' => now()->format('Y-m').'-grace',
            'status' => InvoiceStatus::Overdue,
            'amount_due' => $plan->price,
            'amount_paid' => 0,
            'currency' => 'BRL',
            'due_at' => now()->subDays(3)->startOfDay(),
            'gateway' => 'mercadopago',
        ]);
    }
);

$suspended = $makeCompany(
    'QA Financeiro Suspenso',
    'qa.suspenso',
    [
        'status' => Company::STATUS_SUSPENDED,
        'suspended_at' => now()->subDay(),
        'suspension_reason' => BillingSuspensionReasons::BILLING_PAST_DUE,
    ],
    [
        'status' => Subscription::STATUS_PAST_DUE,
        'next_billing_at' => now()->subDays(10),
    ],
    function ($company, $sub, $plan) {
        Invoice::query()->withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'subscription_id' => $sub->id,
            'plan_id' => $plan->id,
            'number' => 'INV-QA-SUSP',
            'billing_period_key' => now()->format('Y-m').'-susp',
            'status' => InvoiceStatus::Overdue,
            'amount_due' => $plan->price,
            'amount_paid' => 0,
            'currency' => 'BRL',
            'due_at' => now()->subDays(10)->startOfDay(),
            'gateway' => 'mercadopago',
        ]);
    }
);

$adminOnly = $makeCompany(
    'QA Suspensao Admin',
    'qa.adminblock',
    [
        'status' => Company::STATUS_SUSPENDED,
        'suspended_at' => now(),
        'suspension_reason' => BillingSuspensionReasons::ADMINISTRATIVE,
    ],
    ['status' => Subscription::STATUS_ACTIVE],
    null
);

echo json_encode([
    'plan' => $plan->name,
    'active_admin' => 'qa.ativo.admin@qa.expandor.local',
    'grace_admin' => 'qa.grace.admin@qa.expandor.local',
    'suspended_admin' => 'qa.suspenso.admin@qa.expandor.local',
    'suspended_seller' => 'qa.suspenso.seller@qa.expandor.local',
    'suspended_manager' => 'qa.suspenso.manager@qa.expandor.local',
    'admin_block' => 'qa.adminblock.admin@qa.expandor.local',
    'password' => 'password',
    'ids' => [
        'active' => $active->id,
        'grace' => $grace->id,
        'suspended' => $suspended->id,
        'admin_block' => $adminOnly->id,
    ],
], JSON_PRETTY_PRINT)."\n";
