<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        abort_unless(
            $user?->hasPermission('company.manage')
                || $user?->hasPermission('branding.manage')
                || $user?->hasPermission('billing.view')
                || $user?->hasPermission('integrations.view')
                || $user?->hasPermission('onboarding.view')
                || $user?->hasPermission('users.manage')
                || $user?->hasPermission('users.create')
                || $user?->hasPermission('commissions.manage'),
            403,
            'Access denied.'
        );

        $canFieldOps = ($user?->hasPermission('company.manage')
            || $user?->hasPermission('users.manage')) ?? false;

        return view('operations.settings', [
            'company' => $user?->company,
            'canIntegrations' => $user?->hasPermission('integrations.view') ?? false,
            'canCompany' => $user?->hasPermission('company.manage') ?? false,
            'canBranding' => $user?->hasPermission('branding.manage') ?? false,
            'canBilling' => $user?->hasPermission('billing.view') ?? false,
            'canOnboarding' => $user?->hasPermission('onboarding.view') || $user?->hasPermission('onboarding.manage'),
            'canFieldOps' => $canFieldOps,
            'canCommissions' => $user?->hasPermission('commissions.manage') ?? false,
        ]);
    }
}
