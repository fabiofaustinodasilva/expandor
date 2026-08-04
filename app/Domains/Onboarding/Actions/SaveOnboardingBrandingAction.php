<?php

namespace App\Domains\Onboarding\Actions;

use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Branding\Services\BrandingService;
use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Onboarding\Events\OnboardingBrandingCompleted;
use App\Domains\Onboarding\Services\SaasOnboardingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SaveOnboardingBrandingAction
{
    public function __construct(
        protected SaasOnboardingService $saasOnboarding,
        protected BrandingService $branding,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Company $company, array $data, ?User $actor = null, ?UploadedFile $logo = null): Company
    {
        return DB::transaction(function () use ($company, $data, $actor, $logo) {
            $current = $this->branding->forCompany($company);
            $primary = (string) ($data['primary_color'] ?? $current->primaryColor());
            $secondary = (string) ($data['secondary_color'] ?? $current->secondaryColor());
            $highlight = (string) ($data['highlight_color'] ?? $current->highlightColor());

            $colors = array_merge($current->colors, [
                'primary' => $primary,
                'primary_color' => $primary,
                'accent' => $primary,
                'secondary' => $secondary,
                'secondary_color' => $secondary,
                'highlight' => $highlight,
                'highlight_color' => $highlight,
                'accent_2' => $highlight,
            ]);

            $files = [];
            if ($logo instanceof UploadedFile) {
                $files['logo'] = $logo;
            }

            $this->branding->update($company, [
                'system_name' => $current->systemName ?: (string) config('app.name', 'Expandor'),
                'display_name' => $data['display_name'] ?? $company->name,
                'slogan' => $data['slogan'] ?? $current->slogan,
                'theme' => ($current->theme ?? BrandTheme::Dark)->value,
                'colors' => $colors,
                'fonts' => $current->fonts,
                'support_email' => $current->supportEmail ?? $company->email,
                'support_phone' => $current->supportPhone ?? $company->phone,
                'socials' => $current->socials,
                'custom_domain' => $current->customDomain,
                'custom_css' => $current->customCss,
            ], $files);

            if ($logo instanceof UploadedFile) {
                $brandModel = $company->fresh()?->brand;
                if ($brandModel?->logo) {
                    $company->forceFill(['logo' => $brandModel->logo])->save();
                }
            }

            $company = $this->saasOnboarding->advanceTo($company->fresh(), SaasOnboardingService::STEP_FINISH);
            OnboardingBrandingCompleted::dispatch($company, $actor, [
                'display_name' => $data['display_name'] ?? $company->name,
                'primary_color' => $primary,
            ]);

            return $company;
        });
    }
}
