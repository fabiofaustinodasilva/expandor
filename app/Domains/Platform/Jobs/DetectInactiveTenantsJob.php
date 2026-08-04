<?php

namespace App\Domains\Platform\Jobs;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Services\ActivationEventRecorder;
use App\Domains\Platform\Services\ActivationIntelligenceService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Detecta tenants inativos e registra customer.inactive (base para outreach futuro).
 */
class DetectInactiveTenantsJob implements ShouldQueue
{
    use Queueable;

    public function handle(ActivationEventRecorder $events): void
    {
        Company::query()
            ->where('is_system', false)
            ->where('status', Company::STATUS_ACTIVE)
            ->orderBy('id')
            ->chunkById(50, function ($companies) use ($events): void {
                foreach ($companies as $company) {
                    $lastLogin = User::query()
                        ->withoutGlobalScopes()
                        ->where('company_id', $company->id)
                        ->max('last_login_at');

                    if ($lastLogin === null) {
                        continue;
                    }

                    $loginAt = Carbon::parse($lastLogin);
                    if ($loginAt->gt(now()->subDays(ActivationIntelligenceService::INACTIVE_DAYS))) {
                        continue;
                    }

                    $recent = AuditLog::query()
                        ->withoutGlobalScopes()
                        ->where('company_id', $company->id)
                        ->where('action', 'customer.inactive')
                        ->where('created_at', '>=', now()->subDays(7))
                        ->exists();

                    if ($recent) {
                        continue;
                    }

                    $events->inactive($company, null, [
                        'last_login_at' => $loginAt->toIso8601String(),
                        'days' => ActivationIntelligenceService::INACTIVE_DAYS,
                    ]);
                }
            });
    }
}
