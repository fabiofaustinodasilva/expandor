<?php

use App\Http\Controllers\Api\Mobile\V1\OnboardingController as MobileOnboardingController;
use App\Http\Controllers\Api\Mobile\V1\Auth\AuthController as MobileAuthController;
use App\Http\Controllers\Api\Mobile\V1\CampaignController as MobileCampaignController;
use App\Http\Controllers\Api\Mobile\V1\DashboardController as MobileDashboardController;
use App\Http\Controllers\Api\Mobile\V1\SyncController as MobileSyncController;
use App\Http\Controllers\Api\Mobile\V1\AgendaOpsController as MobileAgendaOpsController;
use App\Http\Controllers\Api\Mobile\V1\BootstrapController as MobileBootstrapController;
use App\Http\Controllers\Api\Mobile\V1\CatalogOpsController as MobileCatalogOpsController;
use App\Http\Controllers\Api\Mobile\V1\MapOpsController as MobileMapOpsController;
use App\Http\Controllers\Api\Mobile\V1\PointOpsController as MobilePointOpsController;
use App\Http\Controllers\Api\Mobile\V1\PointVisitOpsController as MobilePointVisitOpsController;
use App\Http\Controllers\Api\Mobile\V1\VisitController as MobileVisitController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Branding\BrandingController;
use App\Http\Controllers\Api\V1\Company\UserController;
use App\Http\Controllers\Api\V1\Maps\MapController;
use App\Http\Controllers\Api\V1\Onboarding\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware([
        'auth:sanctum',
        'seller.single-session',
        'tenancy.initialize',
        'tenancy.active',
    ])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::get('/branding', [BrandingController::class, 'show']);

        Route::get('/onboarding', [OnboardingController::class, 'show']);
        Route::post('/onboarding/step', [OnboardingController::class, 'completeStep']);
        Route::post('/onboarding/demo-data', [OnboardingController::class, 'demoData']);
        Route::post('/onboarding/finish', [OnboardingController::class, 'finish']);

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:users.view');

        Route::get('/maps/markers', [MapController::class, 'markers'])
            ->middleware('permission:maps.view');
    });
});

Route::prefix('mobile/v1')->middleware('throttle:api')->group(function (): void {
    Route::post('/login', [MobileAuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware([
        'auth:sanctum',
        'seller.single-session',
        'tenancy.initialize',
        'tenancy.active',
        'permission:sales_app.access',
        'mobile.seller',
    ])->group(function (): void {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/logout', [MobileAuthController::class, 'logout']);

        Route::get('/dashboard', MobileDashboardController::class);
        Route::get('/onboarding', [MobileOnboardingController::class, 'show']);
        Route::get('/campaigns', [MobileCampaignController::class, 'index']);
        Route::get('/campaign/{campaign}/properties', [MobileCampaignController::class, 'properties']);
        Route::get('/campaign/{campaign}/markers', [MobileCampaignController::class, 'markers']);

        Route::post('/visits', [MobileVisitController::class, 'store']);
        Route::get('/pending-sync', [MobileSyncController::class, 'pending']);

        Route::get('/bootstrap', MobileBootstrapController::class);
        Route::get('/map/config', [MobileMapOpsController::class, 'config']);
        Route::get('/markers', [MobileMapOpsController::class, 'markers'])
            ->middleware('permission:maps.view');

        Route::get('/points', [MobilePointOpsController::class, 'index']);
        Route::post('/points', [MobilePointOpsController::class, 'store'])
            ->middleware('throttle:60,1');
        Route::post('/first-approach', [\App\Http\Controllers\Api\Mobile\V1\FirstApproachOpsController::class, 'store'])
            ->middleware('throttle:60,1');
        Route::get('/points/{point}', [MobilePointOpsController::class, 'show']);
        Route::put('/points/{point}/location', [MobilePointOpsController::class, 'adjustLocation'])
            ->middleware('throttle:60,1');
        Route::post('/points/{point}/visits', [MobilePointVisitOpsController::class, 'store'])
            ->middleware('throttle:60,1');
        Route::post('/points/{point}/sales', [MobilePointVisitOpsController::class, 'sale'])
            ->middleware('throttle:60,1');

        Route::get('/agenda', [MobileAgendaOpsController::class, 'index']);
        Route::post('/follow-ups/{followUp}/complete', [MobileAgendaOpsController::class, 'complete'])
            ->middleware('throttle:60,1');

        Route::get('/products', [MobileCatalogOpsController::class, 'products']);
        Route::get('/commissions', [MobileCatalogOpsController::class, 'commissions']);
        Route::get('/results', [MobileCatalogOpsController::class, 'results']);
        Route::get('/territory', [MobileCatalogOpsController::class, 'territory']);
    });
});
