<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\User;
use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Payments\Models\Payment;
use Illuminate\Support\Facades\DB;

$companies = Company::query()->withoutGlobalScopes()->withTrashed()->orderBy('id')->get([
    'id', 'name', 'email', 'document', 'status', 'is_system', 'created_at', 'deleted_at',
]);

$out = [
    'companies' => $companies->map(function ($c) {
        $users = User::query()->withoutGlobalScopes()->where('company_id', $c->id)->count();
        $subs = DB::table('subscriptions')->where('company_id', $c->id)->count();
        $invoices = DB::table('invoices')->where('company_id', $c->id)->count();
        $payments = DB::table('payments')->where('company_id', $c->id)->count();
        $campaigns = DB::table('campaigns')->where('company_id', $c->id)->count();
        $properties = DB::table('properties')->where('company_id', $c->id)->count();
        $visits = DB::table('visits')->where('company_id', $c->id)->count();
        $name = (string) $c->name;
        $email = (string) $c->email;
        $qaHint = preg_match('/qa|teste|test|demo|hot.?fix|fid |sem setup|invoice|window|email ruim|expandor\.local|@qa\./i', $name.' '.$email) === 1;

        return [
            'id' => $c->id,
            'name' => $c->name,
            'email' => $c->email,
            'is_system' => (bool) $c->is_system,
            'status' => $c->status,
            'deleted_at' => $c->deleted_at?->toDateString(),
            'users' => $users,
            'subscriptions' => $subs,
            'invoices' => $invoices,
            'payments' => $payments,
            'campaigns' => $campaigns,
            'properties' => $properties,
            'visits' => $visits,
            'qa_heuristic' => $qaHint,
        ];
    })->values(),
    'platform_admins' => User::query()->withoutGlobalScopes()
        ->where('is_platform_admin', true)
        ->get(['id', 'name', 'email', 'company_id', 'status'])
        ->toArray(),
    'plans' => Plan::query()->orderBy('id')->get(['id', 'name', 'slug', 'price', 'status'])->toArray(),
    'payments' => Payment::query()->withoutGlobalScopes()->orderBy('id')->get([
        'id', 'company_id', 'amount', 'status', 'gateway', 'gateway_payment_id',
    ])->toArray(),
    'marketplace_leads' => MarketplaceLead::query()->withoutGlobalScopes()->orderBy('id')->get([
        'id', 'name', 'email', 'company_name', 'status', 'source',
    ])->map(fn ($l) => $l->only(['id', 'name', 'email', 'company_name', 'status', 'source']))->values(),
];

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n";
