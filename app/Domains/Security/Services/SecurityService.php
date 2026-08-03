<?php

namespace App\Domains\Security\Services;

use App\Domains\Audit\Models\AuditLog;
use App\Domains\Company\Models\User;
use App\Domains\Security\DTOs\ProductionHealthStatus;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SecurityService
{
    public function __construct(
        protected TenantContext $tenant,
    ) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function recordAudit(
        string $action,
        ?User $user = null,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?Request $request = null,
        ?int $companyId = null,
    ): AuditLog {
        $request ??= request();
        $companyId ??= $user?->company_id ?? $this->tenant->id();

        $log = AuditLog::query()->withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
        ]);

        $this->structuredLog('audit.recorded', [
            'action' => $action,
            'company_id' => $companyId,
            'user_id' => $user?->id,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'ip' => $request?->ip(),
        ]);

        return $log;
    }

    public function recordFailedLogin(string $email, Request $request): void
    {
        $this->structuredLog('auth.login_failed', [
            'email' => $email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $user = User::query()
            ->withoutGlobalScopes()
            ->where('email', $email)
            ->first();

        $this->recordAudit(
            action: 'auth.login_failed',
            user: $user,
            newValues: ['email' => $email],
            request: $request,
            companyId: $user?->company_id,
        );
    }

    public function recordSuccessfulLogin(User $user, Request $request): void
    {
        $this->structuredLog('auth.login_succeeded', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'ip' => $request->ip(),
        ]);

        $this->recordAudit(
            action: 'auth.login_succeeded',
            user: $user,
            newValues: ['email' => $user->email],
            request: $request,
            companyId: $user->company_id,
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function structuredLog(string $event, array $context = []): void
    {
        Log::channel('security')->info($event, array_merge([
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
        ], $context));
    }

    public function productionHealth(): ProductionHealthStatus
    {
        $checks = [];

        try {
            DB::select('select 1');
            $checks['database'] = ['ok' => true, 'message' => 'Conexão OK'];
        } catch (Throwable $exception) {
            $checks['database'] = ['ok' => false, 'message' => $exception->getMessage()];
        }

        try {
            Cache::put('security:health', 'ok', 10);
            $checks['cache'] = [
                'ok' => Cache::get('security:health') === 'ok',
                'message' => 'Cache OK',
            ];
        } catch (Throwable $exception) {
            $checks['cache'] = ['ok' => false, 'message' => $exception->getMessage()];
        }

        $failedJobs = 0;
        try {
            if (Schema::hasTable('failed_jobs')) {
                $failedJobs = (int) DB::table('failed_jobs')->count();
            }
            $checks['failed_jobs'] = [
                'ok' => $failedJobs === 0,
                'message' => $failedJobs === 0
                    ? 'Nenhum job falho'
                    : "{$failedJobs} job(s) falho(s)",
                'count' => $failedJobs,
            ];
        } catch (Throwable $exception) {
            $checks['failed_jobs'] = ['ok' => false, 'message' => $exception->getMessage()];
        }

        $ok = collect($checks)->every(fn ($check) => (bool) ($check['ok'] ?? false));

        return new ProductionHealthStatus(
            ok: $ok,
            checks: $checks,
            failedJobs: $failedJobs,
        );
    }

    public function loginMaxAttempts(): int
    {
        return (int) config('security.login.max_attempts', 5);
    }

    public function loginDecaySeconds(): int
    {
        return (int) config('security.login.decay_seconds', 60);
    }
}
