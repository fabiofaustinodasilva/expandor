<?php

namespace App\Providers;

use App\Domains\AI\Models\AIConversation;
use App\Domains\AI\Policies\AIConversationPolicy;
use App\Domains\Audit\Models\AuditLog;
use App\Domains\Billing\Policies\BillingPolicy;
use App\Domains\Branding\Models\Brand;
use App\Domains\Branding\Policies\BrandPolicy;
use App\Domains\Branding\Services\BrandingService;
use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Campaigns\Policies\CampaignPolicy;
use App\Domains\Communication\Events\MessageQueuedForDelivery;
use App\Domains\Communication\Events\MessageRegistered;
use App\Domains\Communication\Listeners\DispatchWhatsAppSendJob;
use App\Domains\Communication\Listeners\LogMessageRegistered;
use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Models\MessageTemplate;
use App\Domains\Communication\Policies\MessagePolicy;
use App\Domains\Communication\Policies\MessageTemplatePolicy;
use App\Domains\Auth\Models\PersonalAccessToken;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Company\Policies\CompanyPolicy;
use App\Domains\Company\Policies\UserPolicy;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Policies\CompanyIntegrationPolicy;
use App\Domains\Onboarding\DTOs\SaasOnboardingBanner;
use App\Domains\Onboarding\Events\OnboardingBrandingCompleted;
use App\Domains\Onboarding\Events\OnboardingCompanyCompleted;
use App\Domains\Onboarding\Events\OnboardingCompleted;
use App\Domains\Onboarding\Events\OnboardingCustomerCreated;
use App\Domains\Onboarding\Events\OnboardingDealCreated;
use App\Domains\Onboarding\Events\OnboardingDismissed;
use App\Domains\Onboarding\Events\OnboardingStarted;
use App\Domains\Onboarding\Events\OnboardingStepSkipped;
use App\Domains\Onboarding\Events\OnboardingTeamCompleted;
use App\Domains\Onboarding\Listeners\RecordSaasOnboardingAudit;
use App\Domains\Onboarding\Policies\OnboardingPolicy;
use App\Domains\Onboarding\Services\OnboardingService;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use App\Domains\Marketplace\Growth\Events\MarketplaceGrowthEventRecorded;
use App\Domains\Marketplace\Growth\Events\MarketplaceLeadCreated;
use App\Domains\SaasGrowth\Listeners\SyncSaasTrialMilestones;
use App\Domains\Marketplace\Growth\Listeners\RecordLeadCreatedAnalytics;
use App\Domains\Marketplace\Growth\Listeners\SyncTrialActivationFromOnboarding;
use App\Domains\Marketplace\Policies\MarketplacePolicy;
use App\Domains\Marketplace\Revenue\Events\MarketplaceLeadHotDetected;
use App\Domains\Marketplace\Revenue\Events\MarketplaceLeadScored;
use App\Domains\Marketplace\Revenue\Events\MarketplacePipelineChanged;
use App\Domains\Marketplace\Revenue\Listeners\BootstrapLeadRevenueOnCreated;
use App\Domains\Marketplace\Revenue\Listeners\RecordHotLeadAnalytics;
use App\Domains\Marketplace\Revenue\Listeners\RecordLeadScoredAnalytics;
use App\Domains\Marketplace\Revenue\Listeners\RecordPipelineChangedAnalytics;
use App\Domains\Marketplace\Revenue\Listeners\RescoreLeadOnGrowthEvent;
use App\Domains\Platform\Listeners\SyncActivationEventsFromOnboarding;
use App\Domains\Platform\Policies\PlatformPolicy;
use App\Domains\Sales\Properties\Models\Address;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Policies\AddressPolicy;
use App\Domains\Sales\Properties\Policies\PropertyPolicy;
use App\Domains\Sales\Residents\Models\Resident;
use App\Domains\Sales\Residents\Policies\ResidentPolicy;
use App\Domains\Sales\Territory\Models\City;
use App\Domains\Sales\Territory\Models\Sector;
use App\Domains\Sales\Territory\Policies\CityPolicy;
use App\Domains\Sales\Territory\Policies\SectorPolicy;
use App\Domains\Security\Policies\AuditLogPolicy;
use App\Domains\Security\Policies\PrivacyPolicy;
use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Policies\TrainingCategoryPolicy;
use App\Domains\Training\Policies\TrainingContentPolicy;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Commissions\Policies\SalesCommissionPolicy;
use App\Domains\CRM\Models\CommissionRule;
use App\Domains\CRM\Models\Lead;
use App\Domains\CRM\Models\Opportunity;
use App\Domains\CRM\Models\SalesGoal;
use App\Domains\CRM\Policies\CommissionRulePolicy;
use App\Domains\CRM\Policies\LeadPolicy;
use App\Domains\CRM\Policies\OpportunityPolicy;
use App\Domains\CRM\Policies\SalesGoalPolicy;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Policies\ProductPolicy;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Policies\FollowUpPolicy;
use App\Domains\Visits\Policies\VisitPolicy;
use App\Support\CommercialTerminology;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Terminologia comercial (UX) — disponível em todas as views Blade.
        View::share('commercial', CommercialTerminology::class);

        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(City::class, CityPolicy::class);
        Gate::policy(Sector::class, SectorPolicy::class);
        Gate::policy(Address::class, AddressPolicy::class);
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(Resident::class, ResidentPolicy::class);
        Gate::policy(Visit::class, VisitPolicy::class);
        Gate::policy(FollowUp::class, FollowUpPolicy::class);
        Gate::policy(TrainingCategory::class, TrainingCategoryPolicy::class);
        Gate::policy(TrainingContent::class, TrainingContentPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(MessageTemplate::class, MessageTemplatePolicy::class);
        Gate::policy(AIConversation::class, AIConversationPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Brand::class, BrandPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Opportunity::class, OpportunityPolicy::class);
        Gate::policy(SalesGoal::class, SalesGoalPolicy::class);
        Gate::policy(CommissionRule::class, CommissionRulePolicy::class);
        Gate::policy(SalesCommission::class, SalesCommissionPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(CompanyIntegration::class, CompanyIntegrationPolicy::class);

        Gate::define('billing.view', [BillingPolicy::class, 'view']);
        Gate::define('billing.manage', [BillingPolicy::class, 'manage']);
        Gate::define('onboarding.view', [OnboardingPolicy::class, 'view']);
        Gate::define('onboarding.manage', [OnboardingPolicy::class, 'manage']);
        Gate::define('platform.access', [PlatformPolicy::class, 'access']);
        Gate::define('platform.manageCompanies', [PlatformPolicy::class, 'manageCompanies']);
        Gate::define('platform.managePlans', [PlatformPolicy::class, 'managePlans']);
        Gate::define('platform.impersonate', [PlatformPolicy::class, 'impersonate']);
        Gate::define('platform.manageFeatureFlags', [PlatformPolicy::class, 'manageFeatureFlags']);
        Gate::define('platform.viewHealth', [PlatformPolicy::class, 'viewHealth']);
        Gate::define('platform.manageBranding', [PlatformPolicy::class, 'manageBranding']);
        Gate::define('marketplace.manage', [MarketplacePolicy::class, 'manage']);
        Gate::define('privacy.view', [PrivacyPolicy::class, 'view']);
        Gate::define('privacy.manage', [PrivacyPolicy::class, 'manage']);

        Event::listen(MessageRegistered::class, LogMessageRegistered::class);
        Event::listen(MessageQueuedForDelivery::class, DispatchWhatsAppSendJob::class);

        Event::listen(OnboardingStarted::class, [RecordSaasOnboardingAudit::class, 'handleStarted']);
        Event::listen(OnboardingCompanyCompleted::class, [RecordSaasOnboardingAudit::class, 'handleCompanyCompleted']);
        Event::listen(OnboardingTeamCompleted::class, [RecordSaasOnboardingAudit::class, 'handleTeamCompleted']);
        Event::listen(OnboardingCustomerCreated::class, [RecordSaasOnboardingAudit::class, 'handleCustomerCreated']);
        Event::listen(OnboardingDealCreated::class, [RecordSaasOnboardingAudit::class, 'handleDealCreated']);
        Event::listen(OnboardingBrandingCompleted::class, [RecordSaasOnboardingAudit::class, 'handleBrandingCompleted']);
        Event::listen(OnboardingStepSkipped::class, [RecordSaasOnboardingAudit::class, 'handleStepSkipped']);
        Event::listen(OnboardingDismissed::class, [RecordSaasOnboardingAudit::class, 'handleDismissed']);
        Event::listen(OnboardingCompleted::class, [RecordSaasOnboardingAudit::class, 'handleCompleted']);

        Event::listen(OnboardingStarted::class, [SyncActivationEventsFromOnboarding::class, 'handleStarted']);
        Event::listen(OnboardingCustomerCreated::class, [SyncActivationEventsFromOnboarding::class, 'handleCustomer']);
        Event::listen(OnboardingDealCreated::class, [SyncActivationEventsFromOnboarding::class, 'handleDeal']);
        Event::listen(OnboardingCompleted::class, [SyncActivationEventsFromOnboarding::class, 'handleCompleted']);
        Event::listen(OnboardingCompleted::class, SyncTrialActivationFromOnboarding::class);
        Event::listen(MarketplaceLeadCreated::class, RecordLeadCreatedAnalytics::class);
        Event::listen(MarketplaceLeadCreated::class, BootstrapLeadRevenueOnCreated::class);
        Event::listen(MarketplaceGrowthEventRecorded::class, RescoreLeadOnGrowthEvent::class);
        Event::listen(MarketplaceLeadScored::class, RecordLeadScoredAnalytics::class);
        Event::listen(MarketplaceLeadHotDetected::class, RecordHotLeadAnalytics::class);
        Event::listen(MarketplacePipelineChanged::class, RecordPipelineChangedAnalytics::class);

        Event::listen(OnboardingStarted::class, [SyncSaasTrialMilestones::class, 'handleStarted']);
        Event::listen(OnboardingCompanyCompleted::class, [SyncSaasTrialMilestones::class, 'handleCompany']);
        Event::listen(OnboardingTeamCompleted::class, [SyncSaasTrialMilestones::class, 'handleTeam']);
        Event::listen(OnboardingCustomerCreated::class, [SyncSaasTrialMilestones::class, 'handleCustomer']);
        Event::listen(OnboardingDealCreated::class, [SyncSaasTrialMilestones::class, 'handleDeal']);
        Event::listen(OnboardingCompleted::class, [SyncSaasTrialMilestones::class, 'handleActivated']);

        Event::listen(DiagnosingHealth::class, function (): void {
            DB::select('select 1');
        });

        View::composer(['layouts.app', 'layouts.operational', 'layouts.sales-app', 'auth.login', 'auth.forgot-password', 'auth.reset-password'], function ($view): void {
            $user = auth()->user();

            if ($user instanceof User) {
                $user->loadMissing(['company', 'role']);
            }

            $view->with('brand', app(BrandingService::class)->resolveForRequest());

            $onboardingStatus = null;
            $trialBanner = null;
            $saasOnboardingBanner = null;
            if ($user instanceof User && ! $user->isPlatformAdmin() && $user->company) {
                try {
                    $onboardingStatus = app(OnboardingService::class)->status($user->company);
                } catch (\Throwable) {
                    $onboardingStatus = null;
                }

                try {
                    $trialBanner = app(\App\Domains\Onboarding\Services\TrialBannerService::class)
                        ->forCompany($user->company);
                } catch (\Throwable) {
                    $trialBanner = null;
                }

                try {
                    if ($user->company->needsSaasOnboarding()) {
                        $progress = app(SaasOnboardingService::class)->progress($user->company);
                        $saasOnboardingBanner = new SaasOnboardingBanner(
                            show: true,
                            percent: $progress->percent,
                            continueUrl: $progress->continueUrl,
                        );
                    }
                } catch (\Throwable) {
                    $saasOnboardingBanner = null;
                }
            }

            $view->with('onboardingStatus', $onboardingStatus);
            $view->with('trialBanner', $trialBanner);
            $view->with('saasOnboardingBanner', $saasOnboardingBanner);
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $max = (int) config('security.login.max_attempts', 5);

            return Limit::perMinute(max(1, $max))
                ->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)
                ->by(strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('trial-signup', function (Request $request) {
            $perMinute = max(1, (int) config('acquisition.signup.per_minute', 3));

            return [
                Limit::perMinute($perMinute)->by('trial-min:'.$request->ip()),
                Limit::perHour(max(1, (int) config('acquisition.signup.per_hour', 5)))
                    ->by('trial-hour:'.$request->ip()),
            ];
        });

        RateLimiter::for('api', function (Request $request) {
            $max = (int) config('security.api.max_attempts', 60);

            return Limit::perMinute(max(1, $max))
                ->by((string) ($request->user()?->id ?: $request->ip()));
        });
    }
}
