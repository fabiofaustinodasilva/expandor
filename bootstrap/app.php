<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();
            if ($user instanceof \App\Domains\Company\Models\User && $user->isPlatformAdmin()) {
                return '/platform';
            }

            return '/dashboard';
        });

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'webhooks/mercadopago',
            'api/mobile/*',
        ]);

        $middleware->alias([
            'tenancy.initialize' => \App\Tenancy\Middleware\InitializeTenancy::class,
            'tenancy.active' => \App\Tenancy\Middleware\EnsureTenantIsActive::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'platform.admin' => \App\Domains\Platform\Middleware\EnsurePlatformAdmin::class,
            'marketplace.attribution' => \App\Domains\Marketplace\Growth\Middleware\CaptureMarketplaceAttribution::class,
            'presence.touch' => \App\Http\Middleware\TouchUserPresence::class,
            'seller.single-session' => \App\Http\Middleware\EnforceSellerSingleSession::class,
            'mobile.seller' => \App\Http\Middleware\EnsureMobileSeller::class,
        ]);

        // Auth -> Tenancy -> Route Model Binding
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Tenancy\Middleware\InitializeTenancy::class,
        );

        $middleware->prependToGroup('api', [
            \App\Http\Middleware\PreferStatelessBearer::class,
        ]);

        $middleware->appendToGroup('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->job(new \App\Domains\Payments\Jobs\SyncSubscriptionsJob)->hourly();
        $schedule->job(new \App\Domains\Payments\Jobs\RetryFailedPaymentsJob)->hourly();
        $schedule->job(new \App\Domains\Payments\Jobs\ExpireTrialsJob)->daily();
        $schedule->job(new \App\Domains\Payments\Jobs\RenewSubscriptionsJob)->daily();
        $schedule->job(new \App\Domains\Payments\Jobs\GenerateSubscriptionInvoicesJob)->daily();
        $schedule->job(new \App\Domains\Payments\Jobs\EnforceBillingDelinquencyJob)->daily();
        $schedule->job(new \App\Domains\Platform\Jobs\DetectInactiveTenantsJob)->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não autenticado.',
                    'code' => 'unauthenticated',
                ], 401);
            }

            return null;
        });
    })->create();
