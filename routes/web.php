<?php

use App\Http\Controllers\Web\Marketplace\MarketplaceAnalyticsController;
use App\Http\Controllers\Web\Marketplace\MarketplaceController;
use App\Http\Controllers\Web\Marketplace\MarketplaceGrowthController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplaceAnalyticsDashboardController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplaceCampaignsController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplaceCasesController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplaceIntelligenceController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplaceLeadsController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplaceMediaController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplacePipelineController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplaceSectionController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplaceSegmentsController;
use App\Http\Controllers\Web\Platform\Marketplace\MarketplaceSettingsController;
use App\Http\Controllers\Web\Platform\Marketplace\MercadoPagoSettingsController;
use App\Http\Controllers\Web\Onboarding\SaasOnboardingController;
use App\Http\Controllers\Web\Onboarding\SetupWizardController;
use App\Http\Controllers\Web\Onboarding\TourController;
use App\Http\Controllers\Web\Onboarding\TrialConversionController;
use App\Http\Controllers\Web\Payments\CheckoutController;
use App\Http\Controllers\Web\Payments\SubscriptionController;
use App\Http\Controllers\Web\Payments\WebhookController;
use App\Http\Controllers\Web\AI\AIConversationController;
use App\Http\Controllers\Web\Acquisition\TrialSignupController;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Billing\CompanyPlanController;
use App\Http\Controllers\Web\Branding\BrandingController;
use App\Http\Controllers\Web\Communication\MessageController;
use App\Http\Controllers\Web\Communication\MessageTemplateController;
use App\Http\Controllers\Web\Campaigns\CampaignController;
use App\Http\Controllers\Web\Company\CompanyController;
use App\Http\Controllers\Web\Company\UserController;
use App\Http\Controllers\Web\Profile\ProfileController;
use App\Http\Controllers\Web\Dashboard\DashboardController;
use App\Http\Controllers\Web\Maps\MapController;
use App\Http\Controllers\Web\Maps\MapFirstApproachController;
use App\Http\Controllers\Web\Maps\MapPointController;
use App\Http\Controllers\Web\Maps\MapVisitController;
use App\Http\Controllers\Web\Operations\IntegrationsController;
use App\Http\Controllers\Web\Operations\MoreController;
use App\Http\Controllers\Web\Operations\FieldOperationsSettingsController;
use App\Http\Controllers\Web\Operations\SaleSettingsController;
use App\Http\Controllers\Web\Operations\SettingsController;
use App\Http\Controllers\Web\Operations\TeamController;
use App\Http\Controllers\Web\Platform\FeatureFlagController;
use App\Http\Controllers\Web\Platform\SaasHealthController;
use App\Http\Controllers\Web\Platform\SaasIntelligenceController;
use App\Http\Controllers\Web\Platform\HealthScoreController;
use App\Http\Controllers\Web\Platform\ImpersonationController;
use App\Http\Controllers\Web\Platform\PlatformBillingController;
use App\Http\Controllers\Web\Platform\PlatformBrandingController;
use App\Http\Controllers\Web\Platform\PlatformCompanyController;
use App\Http\Controllers\Web\Platform\PlatformOwnerProfileController;
use App\Http\Controllers\Web\Platform\PlatformDashboardController;
use App\Http\Controllers\Web\Platform\PlatformPlanController;
use App\Http\Controllers\Web\Platform\PlatformSubscriptionController;
use App\Http\Controllers\Web\Security\AuditLogController;
use App\Http\Controllers\Web\Security\PrivacyController;
use App\Http\Controllers\Web\SalesApp\SalesAppCampaignController;
use App\Http\Controllers\Web\SalesApp\SalesAppDashboardController;
use App\Http\Controllers\Web\SalesApp\SalesAppFollowUpController;
use App\Http\Controllers\Web\SalesApp\SalesAppTrainingController;
use App\Http\Controllers\Web\Training\TrainingCategoryController;
use App\Http\Controllers\Web\Training\TrainingContentController;
use App\Http\Controllers\Web\Sales\Properties\AddressController;
use App\Http\Controllers\Web\Sales\Properties\PropertyController;
use App\Http\Controllers\Web\Sales\Residents\ResidentController;
use App\Http\Controllers\Web\Sales\Territory\CityController;
use App\Http\Controllers\Web\Sales\Territory\SectorController;
use App\Http\Controllers\Web\CRM\CommissionController;
use App\Http\Controllers\Web\CRM\CrmDashboardController;
use App\Http\Controllers\Web\CRM\LeadController;
use App\Http\Controllers\Web\CRM\OpportunityController;
use App\Http\Controllers\Web\CRM\SalesGoalController;
use App\Http\Controllers\Web\Commissions\ProductController as CommissionProductController;
use App\Http\Controllers\Web\Commissions\SalesCommissionController;
use App\Http\Controllers\Web\Visits\FollowUpController;
use App\Http\Controllers\Web\Visits\VisitController;
use Illuminate\Support\Facades\Route;

Route::middleware('marketplace.attribution')->group(function (): void {
    Route::get('/', [MarketplaceController::class, 'home'])->name('marketplace.home');
    Route::get('/marketplace', [MarketplaceController::class, 'home'])->name('marketplace.landing');
    Route::get('/marketplace/{segment}', [MarketplaceGrowthController::class, 'segment'])
        ->where('segment', '^[a-z0-9]+(?:-[a-z0-9]+)*$')
        ->name('marketplace.segment');
    Route::get('/planos', [MarketplaceController::class, 'plans'])->name('marketplace.plans');
    Route::get('/assinar', [MarketplaceController::class, 'subscribe'])->name('marketplace.subscribe');
    Route::post('/marketplace/leads', [MarketplaceGrowthController::class, 'storeLead'])
        ->middleware('throttle:20,1')
        ->name('marketplace.leads.store');
    Route::post('/marketplace/roi', [MarketplaceGrowthController::class, 'calculateRoi'])
        ->middleware('throttle:30,1')
        ->name('marketplace.roi.calculate');
});
Route::get('/sitemap.xml', [MarketplaceController::class, 'sitemap'])->name('marketplace.sitemap');
Route::post('/marketplace/events', [MarketplaceAnalyticsController::class, 'store'])
    ->middleware(['throttle:60,1', 'marketplace.attribution'])
    ->name('marketplace.events.store');
Route::get('/assinar/pix', [CheckoutController::class, 'pix'])->name('checkout.pix');
Route::get('/assinar/aguardando', [CheckoutController::class, 'waiting'])->name('checkout.waiting');
Route::get('/assinar/status/{uuid}', [CheckoutController::class, 'status'])->name('checkout.status');

Route::get('/plans', [CheckoutController::class, 'plans'])->name('plans.index');
Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

Route::post('/webhooks/asaas', [WebhookController::class, 'asaas'])->name('webhooks.asaas');
Route::match(['get', 'post'], '/webhooks/mercadopago', [WebhookController::class, 'mercadopago'])->name('webhooks.mercadopago');
Route::post('/webhooks/{provider}', [WebhookController::class, 'handle'])->name('webhooks.provider');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/cadastro', [TrialSignupController::class, 'create'])->name('signup.create');
    Route::post('/cadastro', [TrialSignupController::class, 'store'])
        ->middleware('throttle:trial-signup')
        ->name('signup.store');

    Route::get('/teste-gratis', [TrialSignupController::class, 'create'])->name('trial.create');
    Route::post('/teste-gratis', [TrialSignupController::class, 'store'])
        ->middleware('throttle:trial-signup')
        ->name('trial.store');
});

Route::middleware([
    'auth',
    'tenancy.initialize',
    'tenancy.active',
])->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('/impersonation/exit', [ImpersonationController::class, 'destroy'])->name('impersonation.exit');

    Route::get('/meu-perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/meu-perfil', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('platform.admin')->prefix('platform')->name('platform.')->group(function (): void {
        Route::get('/', PlatformDashboardController::class)->name('dashboard');
        Route::get('/activation', SaasHealthController::class)->name('activation.index');
        Route::get('/saas-intelligence', SaasIntelligenceController::class)->name('saas.intelligence');
        Route::post('/saas-intelligence/recalculate-health', [SaasIntelligenceController::class, 'recalculateHealth'])
            ->name('saas.intelligence.recalculate-health');
        Route::get('/companies/{company}/usage', [SaasIntelligenceController::class, 'companyUsage'])
            ->name('companies.usage');

        Route::get('/profile', [PlatformOwnerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [PlatformOwnerProfileController::class, 'update'])->name('profile.update');

        Route::get('/plans', [PlatformPlanController::class, 'index'])->name('plans.index');
        Route::get('/plans/create', [PlatformPlanController::class, 'create'])->name('plans.create');
        Route::post('/plans', [PlatformPlanController::class, 'store'])->name('plans.store');
        Route::get('/plans/{plan}/edit', [PlatformPlanController::class, 'edit'])->name('plans.edit');
        Route::put('/plans/{plan}', [PlatformPlanController::class, 'update'])->name('plans.update');
        Route::post('/plans/{plan}/activate', [PlatformPlanController::class, 'activate'])->name('plans.activate');
        Route::post('/plans/{plan}/deactivate', [PlatformPlanController::class, 'deactivate'])->name('plans.deactivate');

        Route::get('/billing', [PlatformBillingController::class, 'index'])->name('billing.index');

        Route::get('/companies', [PlatformCompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/create', [PlatformCompanyController::class, 'create'])->name('companies.create');
        Route::post('/companies', [PlatformCompanyController::class, 'store'])->name('companies.store');
        Route::get('/companies/{company}', [PlatformCompanyController::class, 'show'])->name('companies.show');
        Route::get('/companies/{company}/edit', [PlatformCompanyController::class, 'edit'])->name('companies.edit');
        Route::put('/companies/{company}', [PlatformCompanyController::class, 'update'])->name('companies.update');
        Route::delete('/companies/{company}', [PlatformCompanyController::class, 'destroy'])->name('companies.destroy');
        Route::post('/companies/{company}/restore', [PlatformCompanyController::class, 'restore'])->name('companies.restore');
        Route::post('/companies/{company}/suspend', [PlatformCompanyController::class, 'suspend'])->name('companies.suspend');
        Route::post('/companies/{company}/activate', [PlatformCompanyController::class, 'activate'])->name('companies.activate');
        Route::post('/companies/{company}/convert-trial', [PlatformCompanyController::class, 'convertTrial'])->name('companies.convert-trial');
        Route::post('/companies/{company}/reset-admin-password', [PlatformCompanyController::class, 'resetAdminPassword'])->name('companies.reset-admin-password');
        Route::put('/companies/{company}/admin-contact', [PlatformCompanyController::class, 'updateAdminContact'])->name('companies.admin-contact');
        Route::post('/companies/{company}/admin-block', [PlatformCompanyController::class, 'blockAdmin'])->name('companies.admin-block');
        Route::post('/companies/{company}/admin-unblock', [PlatformCompanyController::class, 'unblockAdmin'])->name('companies.admin-unblock');
        Route::post('/companies/{company}/admin-force-logout', [PlatformCompanyController::class, 'forceLogoutAdmin'])->name('companies.admin-force-logout');
        Route::post('/companies/{company}/change-administrator', [PlatformCompanyController::class, 'changeAdministrator'])->name('companies.change-administrator');
        Route::post('/companies/{company}/subscription/renew-trial', [PlatformSubscriptionController::class, 'renewTrial'])->name('companies.subscription.renew-trial');
        Route::post('/companies/{company}/subscription/change-plan', [PlatformSubscriptionController::class, 'changePlan'])->name('companies.subscription.change-plan');
        Route::put('/companies/{company}/subscription/dates', [PlatformSubscriptionController::class, 'updateDates'])->name('companies.subscription.dates');
        Route::post('/companies/{company}/subscription/cancel', [PlatformSubscriptionController::class, 'cancel'])->name('companies.subscription.cancel');
        Route::post('/companies/{company}/subscription/reactivate', [PlatformSubscriptionController::class, 'reactivate'])->name('companies.subscription.reactivate');
        Route::post('/companies/{company}/impersonate', [ImpersonationController::class, 'store'])->name('impersonation.store');
        Route::get('/flags', [FeatureFlagController::class, 'index'])->name('flags.index');
        Route::post('/companies/{company}/flags', [FeatureFlagController::class, 'update'])->name('flags.update');
        Route::get('/health', [HealthScoreController::class, 'index'])->name('health.index');
        Route::post('/health/recalculate', [HealthScoreController::class, 'recalculateAll'])->name('health.recalculate-all');
        Route::post('/companies/{company}/health', [HealthScoreController::class, 'recalculate'])->name('health.recalculate');
        Route::get('/branding', [PlatformBrandingController::class, 'edit'])->name('branding.edit');
        Route::put('/branding', [PlatformBrandingController::class, 'update'])->name('branding.update');

        Route::prefix('marketplace')->name('marketplace.')->group(function (): void {
            Route::get('/settings', [MarketplaceSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('/settings', [MarketplaceSettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/restore-defaults', [MarketplaceSettingsController::class, 'restoreDefaults'])->name('settings.restore');
            Route::get('/preview', [MarketplaceSettingsController::class, 'preview'])->name('preview');
            Route::get('/mercadopago', [MercadoPagoSettingsController::class, 'edit'])->name('mercadopago.edit');
            Route::put('/mercadopago', [MercadoPagoSettingsController::class, 'update'])->name('mercadopago.update');
            Route::post('/mercadopago/test', [MercadoPagoSettingsController::class, 'testConnection'])->name('mercadopago.test');

            Route::get('/sections', [MarketplaceSectionController::class, 'index'])->name('sections.index');
            Route::get('/sections/create', [MarketplaceSectionController::class, 'create'])->name('sections.create');
            Route::post('/sections', [MarketplaceSectionController::class, 'store'])->name('sections.store');
            Route::post('/sections/reorder', [MarketplaceSectionController::class, 'reorder'])->name('sections.reorder');
            Route::get('/sections/{section}/edit', [MarketplaceSectionController::class, 'edit'])->name('sections.edit');
            Route::put('/sections/{section}', [MarketplaceSectionController::class, 'update'])->name('sections.update');
            Route::delete('/sections/{section}', [MarketplaceSectionController::class, 'destroy'])->name('sections.destroy');
            Route::post('/sections/{section}/toggle', [MarketplaceSectionController::class, 'toggle'])->name('sections.toggle');

            Route::get('/media', [MarketplaceMediaController::class, 'index'])->name('media.index');
            Route::post('/media', [MarketplaceMediaController::class, 'store'])->name('media.store');
            Route::post('/media/testimonials', [MarketplaceMediaController::class, 'storeTestimonial'])->name('media.testimonials.store');
            Route::delete('/media/testimonials/{testimonial}', [MarketplaceMediaController::class, 'destroyTestimonial'])->name('media.testimonials.destroy');
            Route::post('/media/faqs', [MarketplaceMediaController::class, 'storeFaq'])->name('media.faqs.store');
            Route::delete('/media/faqs/{faq}', [MarketplaceMediaController::class, 'destroyFaq'])->name('media.faqs.destroy');
            Route::delete('/media/{medium}', [MarketplaceMediaController::class, 'destroy'])->name('media.destroy');

            Route::get('/leads', [MarketplaceLeadsController::class, 'index'])->name('leads.index');
            Route::put('/leads/{lead}', [MarketplaceLeadsController::class, 'updateStatus'])->name('leads.update');
            Route::get('/analytics', MarketplaceAnalyticsDashboardController::class)->name('analytics');
            Route::get('/segments', [MarketplaceSegmentsController::class, 'index'])->name('segments.index');
            Route::post('/segments', [MarketplaceSegmentsController::class, 'store'])->name('segments.store');
            Route::delete('/segments/{segment}', [MarketplaceSegmentsController::class, 'destroy'])->name('segments.destroy');
            Route::get('/cases', [MarketplaceCasesController::class, 'index'])->name('cases.index');
            Route::post('/cases', [MarketplaceCasesController::class, 'store'])->name('cases.store');
            Route::delete('/cases/{case}', [MarketplaceCasesController::class, 'destroy'])->name('cases.destroy');
            Route::get('/campaigns', [MarketplaceCampaignsController::class, 'index'])->name('campaigns.index');
            Route::post('/campaigns', [MarketplaceCampaignsController::class, 'store'])->name('campaigns.store');
            Route::delete('/campaigns/{campaign}', [MarketplaceCampaignsController::class, 'destroy'])->name('campaigns.destroy');

            Route::get('/intelligence', MarketplaceIntelligenceController::class)->name('intelligence');
            Route::get('/pipeline', [MarketplacePipelineController::class, 'index'])->name('pipeline.index');
            Route::put('/pipeline/{pipeline}', [MarketplacePipelineController::class, 'update'])->name('pipeline.update');
        });
    });

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/ai', [AIConversationController::class, 'index'])->name('ai.conversations.index');
    Route::get('/ai/create', [AIConversationController::class, 'create'])->name('ai.conversations.create');
    Route::post('/ai', [AIConversationController::class, 'store'])->name('ai.conversations.store');
    Route::get('/ai/{conversation}', [AIConversationController::class, 'show'])->name('ai.conversations.show');

    Route::get('/communication/messages', [MessageController::class, 'index'])->name('communication.messages.index');
    Route::get('/communication/messages/create', [MessageController::class, 'create'])->name('communication.messages.create');
    Route::post('/communication/messages', [MessageController::class, 'store'])->name('communication.messages.store');
    Route::get('/communication/residents/{resident}/messages', [MessageController::class, 'residentHistory'])->name('communication.messages.history');

    Route::get('/communication/templates', [MessageTemplateController::class, 'index'])->name('communication.templates.index');
    Route::get('/communication/templates/create', [MessageTemplateController::class, 'create'])->name('communication.templates.create');
    Route::post('/communication/templates', [MessageTemplateController::class, 'store'])->name('communication.templates.store');
    Route::get('/communication/templates/{template}/edit', [MessageTemplateController::class, 'edit'])->name('communication.templates.edit');
    Route::put('/communication/templates/{template}', [MessageTemplateController::class, 'update'])->name('communication.templates.update');

    Route::prefix('app')->name('sales-app.')->group(function (): void {
        Route::get('/', SalesAppDashboardController::class)->name('dashboard');
        Route::get('/campaigns', [SalesAppCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('/campaigns/{campaign}/properties', [SalesAppCampaignController::class, 'properties'])->name('campaigns.properties');
        Route::get('/campaigns/{campaign}/properties/{property}/visit', [SalesAppCampaignController::class, 'createVisit'])->name('campaigns.visits.create');
        Route::post('/campaigns/{campaign}/properties/{property}/visit', [SalesAppCampaignController::class, 'storeVisit'])->name('campaigns.visits.store');
        Route::get('/follow-ups', [SalesAppFollowUpController::class, 'index'])->name('follow-ups.index');
        Route::post('/follow-ups/{followUp}/complete', [SalesAppFollowUpController::class, 'complete'])->name('follow-ups.complete');
        Route::get('/training', [SalesAppTrainingController::class, 'index'])->name('training.index');
        Route::get('/training/{content}', [SalesAppTrainingController::class, 'show'])->name('training.show');
        Route::post('/training/{content}/complete', [SalesAppTrainingController::class, 'complete'])->name('training.complete');
    });

    Route::get('/training/categories', [TrainingCategoryController::class, 'index'])->name('training.categories.index');
    Route::get('/training/categories/create', [TrainingCategoryController::class, 'create'])->name('training.categories.create');
    Route::post('/training/categories', [TrainingCategoryController::class, 'store'])->name('training.categories.store');
    Route::get('/training/categories/{category}/edit', [TrainingCategoryController::class, 'edit'])->name('training.categories.edit');
    Route::put('/training/categories/{category}', [TrainingCategoryController::class, 'update'])->name('training.categories.update');
    Route::post('/training/categories/{category}/toggle-status', [TrainingCategoryController::class, 'toggleStatus'])->name('training.categories.toggle');

    Route::get('/training/contents', [TrainingContentController::class, 'index'])->name('training.contents.index');
    Route::get('/training/contents/create', [TrainingContentController::class, 'create'])->name('training.contents.create');
    Route::post('/training/contents', [TrainingContentController::class, 'store'])->name('training.contents.store');
    Route::get('/training/contents/{content}/edit', [TrainingContentController::class, 'edit'])->name('training.contents.edit');
    Route::put('/training/contents/{content}', [TrainingContentController::class, 'update'])->name('training.contents.update');

    Route::get('/map', [MapController::class, 'index'])->name('map.index');
    Route::post('/map/first-approach', [MapFirstApproachController::class, 'store'])->name('map.first-approach');
    Route::post('/map/campaigns/{campaign}/visits', [MapVisitController::class, 'store'])->name('map.visits.store');
    Route::post('/map/points', [MapPointController::class, 'store'])->name('map.points.store');
    Route::get('/map/points/{property}', [MapPointController::class, 'show'])->name('map.points.show');
    Route::put('/map/points/{property}', [MapPointController::class, 'update'])->name('map.points.update');
    Route::put('/map/points/{property}/location', [MapPointController::class, 'adjust'])->name('map.points.adjust');
    Route::delete('/map/points/{property}', [MapPointController::class, 'destroy'])->name('map.points.destroy');

    Route::get('/operacao/equipe', [TeamController::class, 'index'])->name('operations.team');
    Route::post('/operacao/equipe', [TeamController::class, 'store'])->name('operations.team.store');
    Route::put('/operacao/equipe/{user}', [TeamController::class, 'update'])->name('operations.team.update');
    Route::post('/operacao/equipe/{user}/inativar', [TeamController::class, 'deactivate'])->name('operations.team.deactivate');
    Route::post('/operacao/equipe/{user}/ativar', [TeamController::class, 'activate'])->name('operations.team.activate');
    Route::post('/operacao/equipe/{user}/senha', [TeamController::class, 'resetPassword'])->name('operations.team.reset-password');
    Route::put('/operacao/equipe/{user}/permissoes', [TeamController::class, 'updatePermissions'])->name('operations.team.permissions');
    Route::get('/operacao/configuracoes', SettingsController::class)->name('operations.settings');
    Route::get('/operacao/configuracoes/venda', [SaleSettingsController::class, 'edit'])->name('operations.settings.sale');
    Route::put('/operacao/configuracoes/venda', [SaleSettingsController::class, 'update'])->name('operations.settings.sale.update');
    Route::get('/operacao/configuracoes/operacao-de-campo', [FieldOperationsSettingsController::class, 'edit'])->name('operations.settings.field');
    Route::put('/operacao/configuracoes/operacao-de-campo', [FieldOperationsSettingsController::class, 'update'])->name('operations.settings.field.update');
    Route::get('/operacao/integracoes', IntegrationsController::class)->name('operations.integrations');
    Route::get('/operacao/mais', MoreController::class)->name('operations.more');
    Route::get('/operacao/minhas-visitas', \App\Http\Controllers\Web\Operations\MyVisitsController::class)->name('operations.my-visits');

    Route::get('/clientes', [\App\Http\Controllers\Web\Customers\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/clientes/{property}', [\App\Http\Controllers\Web\Customers\CustomerController::class, 'show'])->name('customers.show');

    Route::get('/comissoes', [SalesCommissionController::class, 'index'])->name('commissions.index');
    Route::post('/comissoes/{commission}/aprovar', [SalesCommissionController::class, 'approve'])->name('commissions.approve');
    Route::post('/comissoes/{commission}/pagar', [SalesCommissionController::class, 'markPaid'])->name('commissions.pay');
    Route::get('/operacao/configuracoes/produtos', [CommissionProductController::class, 'index'])->name('commissions.products.index');
    Route::get('/operacao/configuracoes/produtos/criar', [CommissionProductController::class, 'create'])->name('commissions.products.create');
    Route::post('/operacao/configuracoes/produtos', [CommissionProductController::class, 'store'])->name('commissions.products.store');
    Route::get('/operacao/configuracoes/produtos/{product}/editar', [CommissionProductController::class, 'edit'])->name('commissions.products.edit');
    Route::put('/operacao/configuracoes/produtos/{product}', [CommissionProductController::class, 'update'])->name('commissions.products.update');
    Route::delete('/operacao/configuracoes/produtos/{product}', [CommissionProductController::class, 'destroy'])->name('commissions.products.destroy');
    Route::post('/operacao/configuracoes/produtos/{product}/estoque/entrada', [CommissionProductController::class, 'stockEntry'])->name('commissions.products.stock.entry');
    Route::post('/operacao/configuracoes/produtos/{product}/estoque/ajuste', [CommissionProductController::class, 'stockAdjust'])->name('commissions.products.stock.adjust');

    Route::get('/crm', CrmDashboardController::class)->name('crm.dashboard');
    Route::get('/crm/leads', [LeadController::class, 'index'])->name('crm.leads.index');
    Route::get('/crm/leads/create', [LeadController::class, 'create'])->name('crm.leads.create');
    Route::post('/crm/leads', [LeadController::class, 'store'])->name('crm.leads.store');
    Route::get('/crm/leads/{lead}/edit', [LeadController::class, 'edit'])->name('crm.leads.edit');
    Route::put('/crm/leads/{lead}', [LeadController::class, 'update'])->name('crm.leads.update');
    Route::post('/crm/leads/{lead}/convert', [LeadController::class, 'convert'])->name('crm.leads.convert');
    Route::get('/crm/opportunities', [OpportunityController::class, 'index'])->name('crm.opportunities.index');
    Route::get('/crm/opportunities/kanban', [OpportunityController::class, 'kanban'])->name('crm.opportunities.kanban');
    Route::get('/crm/opportunities/create', [OpportunityController::class, 'create'])->name('crm.opportunities.create');
    Route::post('/crm/opportunities', [OpportunityController::class, 'store'])->name('crm.opportunities.store');
    Route::get('/crm/opportunities/{opportunity}/edit', [OpportunityController::class, 'edit'])->name('crm.opportunities.edit');
    Route::put('/crm/opportunities/{opportunity}', [OpportunityController::class, 'update'])->name('crm.opportunities.update');
    Route::post('/crm/opportunities/{opportunity}/move', [OpportunityController::class, 'move'])->name('crm.opportunities.move');
    Route::post('/crm/opportunities/{opportunity}/win', [OpportunityController::class, 'win'])->name('crm.opportunities.win');
    Route::post('/crm/opportunities/{opportunity}/lose', [OpportunityController::class, 'lose'])->name('crm.opportunities.lose');
    Route::get('/crm/goals', [SalesGoalController::class, 'index'])->name('crm.goals.index');
    Route::post('/crm/goals', [SalesGoalController::class, 'store'])->name('crm.goals.store');
    Route::get('/crm/commissions', [CommissionController::class, 'index'])->name('crm.commissions.index');
    Route::post('/crm/commissions/rules', [CommissionController::class, 'storeRule'])->name('crm.commissions.rules.store');

    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
    Route::post('/campaigns/{campaign}/activate', [CampaignController::class, 'activate'])->name('campaigns.activate');
    Route::post('/campaigns/{campaign}/pause', [CampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('/campaigns/{campaign}/finish', [CampaignController::class, 'finish'])->name('campaigns.finish');

    Route::get('/campaigns/{campaign}/visits', [VisitController::class, 'index'])->name('campaigns.visits.index');
    Route::get('/campaigns/{campaign}/visits/create', [VisitController::class, 'create'])->name('campaigns.visits.create');
    Route::post('/campaigns/{campaign}/visits', [VisitController::class, 'store'])->name('campaigns.visits.store');
    Route::get('/visits/{visit}', [VisitController::class, 'show'])->name('visits.show');
    Route::get('/visits/{visit}/follow-ups/create', [FollowUpController::class, 'create'])->name('visits.follow-ups.create');
    Route::post('/visits/{visit}/follow-ups', [FollowUpController::class, 'store'])->name('visits.follow-ups.store');
    Route::get('/follow-ups', [FollowUpController::class, 'index'])->name('follow-ups.index');
    Route::post('/follow-ups/{followUp}/complete', [FollowUpController::class, 'complete'])->name('follow-ups.complete');

    Route::get('/company/plan', [CompanyPlanController::class, 'show'])->name('company.plan.show');

    Route::get('/onboarding', [SaasOnboardingController::class, 'index'])->name('onboarding.index');
    Route::get('/onboarding/company', [SaasOnboardingController::class, 'company'])->name('onboarding.company');
    Route::put('/onboarding/company', [SaasOnboardingController::class, 'updateCompany'])->name('onboarding.company.update');
    Route::get('/onboarding/team', [SaasOnboardingController::class, 'team'])->name('onboarding.team');
    Route::post('/onboarding/team', [SaasOnboardingController::class, 'storeTeam'])->name('onboarding.team.store');
    Route::get('/onboarding/customer', [SaasOnboardingController::class, 'customer'])->name('onboarding.customer');
    Route::post('/onboarding/customer', [SaasOnboardingController::class, 'storeCustomer'])->name('onboarding.customer.store');
    Route::get('/onboarding/deal', [SaasOnboardingController::class, 'deal'])->name('onboarding.deal');
    Route::post('/onboarding/deal', [SaasOnboardingController::class, 'storeDeal'])->name('onboarding.deal.store');
    Route::get('/onboarding/branding', [SaasOnboardingController::class, 'branding'])->name('onboarding.branding');
    Route::post('/onboarding/branding', [SaasOnboardingController::class, 'storeBranding'])->name('onboarding.branding.store');
    Route::get('/onboarding/finish', [SaasOnboardingController::class, 'finish'])->name('onboarding.finish');
    Route::post('/onboarding/complete', [SaasOnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/skip', [SaasOnboardingController::class, 'skip'])->name('onboarding.skip');
    Route::post('/onboarding/dismiss', [SaasOnboardingController::class, 'dismiss'])->name('onboarding.dismiss');
    Route::post('/onboarding/dismiss-ready', [SaasOnboardingController::class, 'dismissWorkspaceReady'])->name('onboarding.dismiss-ready');

    Route::get('/setup/{step?}', [SetupWizardController::class, 'show'])->name('setup.show');
    Route::post('/setup', [SetupWizardController::class, 'store'])->name('setup.store');
    Route::post('/setup/demo', [SetupWizardController::class, 'demo'])->name('setup.demo');
    Route::post('/setup/finish', [SetupWizardController::class, 'finish'])->name('setup.finish');
    Route::get('/trial/converter', [TrialConversionController::class, 'show'])
        ->name('trial.conversion');
    Route::post('/tour/complete', [TourController::class, 'complete'])->name('tour.complete');
    Route::post('/tour/skip', [TourController::class, 'skip'])->name('tour.skip');
    Route::post('/tour/restart', [TourController::class, 'restart'])->name('tour.restart');
    Route::get('/company/subscription', [SubscriptionController::class, 'show'])->name('company.subscription.show');
    Route::post('/company/subscription/upgrade', [SubscriptionController::class, 'upgrade'])->name('company.subscription.upgrade');
    Route::post('/company/subscription/downgrade', [SubscriptionController::class, 'downgrade'])->name('company.subscription.downgrade');
    Route::post('/company/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('company.subscription.cancel');
    Route::get('/company/branding', [BrandingController::class, 'edit'])->name('company.branding.edit');
    Route::post('/company/branding', [BrandingController::class, 'store'])->name('company.branding.store');
    Route::put('/company/branding', [BrandingController::class, 'update'])->name('company.branding.update');
    Route::get('/company/audit', [AuditLogController::class, 'index'])->name('company.audit.index');
    Route::get('/company/privacy', [PrivacyController::class, 'index'])->name('company.privacy.index');
    Route::post('/company/privacy/export', [PrivacyController::class, 'export'])->name('company.privacy.export');
    Route::get('/company/privacy/export/{export}/download', [PrivacyController::class, 'downloadExport'])->name('company.privacy.export.download');
    Route::post('/company/privacy/anonymizations', [PrivacyController::class, 'storeAnonymization'])->name('company.privacy.anonymizations.store');
    Route::post('/company/privacy/anonymizations/{anonymization}/process', [PrivacyController::class, 'processAnonymization'])->name('company.privacy.anonymizations.process');
    Route::post('/company/privacy/consents', [PrivacyController::class, 'storeConsent'])->name('company.privacy.consents.store');
    Route::get('/company/{company}', [CompanyController::class, 'show'])->name('company.show');
    Route::get('/company/{company}/edit', [CompanyController::class, 'edit'])->name('company.edit');
    Route::put('/company/{company}', [CompanyController::class, 'update'])->name('company.update');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

    Route::get('/cities', [CityController::class, 'index'])->name('cities.index');
    Route::get('/cities/create', [CityController::class, 'create'])->name('cities.create');
    Route::post('/cities', [CityController::class, 'store'])->name('cities.store');
    Route::get('/cities/{city}/edit', [CityController::class, 'edit'])->name('cities.edit');
    Route::put('/cities/{city}', [CityController::class, 'update'])->name('cities.update');
    Route::post('/cities/{city}/toggle-status', [CityController::class, 'toggleStatus'])->name('cities.toggle-status');

    Route::get('/sectors', [SectorController::class, 'index'])->name('sectors.index');
    Route::get('/sectors/create', [SectorController::class, 'create'])->name('sectors.create');
    Route::post('/sectors', [SectorController::class, 'store'])->name('sectors.store');
    Route::get('/sectors/{sector}/edit', [SectorController::class, 'edit'])->name('sectors.edit');
    Route::put('/sectors/{sector}', [SectorController::class, 'update'])->name('sectors.update');
    Route::post('/sectors/{sector}/toggle-status', [SectorController::class, 'toggleStatus'])->name('sectors.toggle-status');

    Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::get('/addresses/create', [AddressController::class, 'create'])->name('addresses.create');
    Route::post('/addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::get('/addresses/{address}/edit', [AddressController::class, 'edit'])->name('addresses.edit');
    Route::put('/addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');

    Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
    Route::get('/properties/create', [PropertyController::class, 'create'])->name('properties.create');
    Route::post('/properties', [PropertyController::class, 'store'])->name('properties.store');
    Route::get('/properties/{property}/status', [PropertyController::class, 'editStatus'])->name('properties.status.edit');
    Route::put('/properties/{property}/status', [PropertyController::class, 'updateStatus'])->name('properties.status.update');

    Route::get('/properties/{property}/residents', [ResidentController::class, 'index'])->name('properties.residents.index');
    Route::get('/properties/{property}/residents/create', [ResidentController::class, 'create'])->name('properties.residents.create');
    Route::post('/properties/{property}/residents', [ResidentController::class, 'store'])->name('properties.residents.store');
    Route::get('/residents/{resident}/edit', [ResidentController::class, 'edit'])->name('residents.edit');
    Route::put('/residents/{resident}', [ResidentController::class, 'update'])->name('residents.update');
    Route::get('/residents/{resident}/status', [ResidentController::class, 'editStatus'])->name('residents.status.edit');
    Route::put('/residents/{resident}/status', [ResidentController::class, 'updateStatus'])->name('residents.status.update');
});
