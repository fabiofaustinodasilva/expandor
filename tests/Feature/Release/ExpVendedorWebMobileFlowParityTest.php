<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Paridade web × mobile — fluxo de visita/campanha/venda.
 */
class ExpVendedorWebMobileFlowParityTest extends TestCase
{
    public function test_parity_document_exists(): void
    {
        $this->assertFileExists(base_path('docs/sprint-8234-seller-mobile-api-map/WEB-MOBILE-FLOW-PARITY.md'));
    }

    public function test_bootstrap_exposes_campaign_and_sale_field_context(): void
    {
        $service = (string) file_get_contents(base_path('app/Domains/Mobile/Services/MobileSellerOpsService.php'));

        $this->assertStringContainsString('campaign_context', $service);
        $this->assertStringContainsString('active_campaign_id', $service);
        $this->assertStringContainsString('sale_fields', $service);
        $this->assertStringContainsString('RegisterFirstApproachAction', $service);
        $this->assertStringContainsString('resolveCampaign', $service);
        $this->assertStringContainsString('SaleFieldsPolicyResolver', $service);
    }

    public function test_create_shell_has_minimal_property_form_without_status(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));

        $this->assertStringContainsString('Novo ponto', $prepare);
        $this->assertStringContainsString('Situação / interesse', $prepare);
        $this->assertStringContainsString('create-outcome-list', $prepare);
        $this->assertStringNotContainsString('point-status', $prepare);
        $this->assertStringNotContainsString('SITUAÇÃO', $prepare);
        $this->assertStringNotContainsString('Novo imóvel', $prepare);
        $this->assertStringContainsString('mobileApi.firstApproach', $shell);
        $this->assertStringNotContainsString('point-status', $shell);
    }

    public function test_visit_sheet_matches_web_outcomes_and_campaign_context(): void
    {
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $outcomes = (string) file_get_contents(resource_path('js/mobile/visit-outcomes.js'));

        $this->assertStringContainsString('Registrar visita', $prepare);
        $this->assertStringContainsString('Como foi a abordagem?', $prepare);
        $this->assertStringContainsString('visit-campaign-label', $prepare);
        $this->assertStringContainsString('visit-outcome-list', $prepare);
        $this->assertStringContainsString('visit-return-block', $prepare);
        $this->assertStringContainsString('visit-sale-block', $prepare);
        $this->assertStringContainsString('sale-cart-lines', $prepare);

        $this->assertStringContainsString('interested', $outcomes);
        $this->assertStringContainsString('return_later', $outcomes);
        $this->assertStringContainsString('installation_requested', $outcomes);
        $this->assertStringContainsString('not_home', $outcomes);
        $this->assertStringContainsString('no_interest', $outcomes);

        $this->assertStringContainsString('paintCampaignContext', $shell);
        $this->assertStringContainsString('resolveCampaignIdForSubmit', $shell);
        $this->assertStringContainsString('completeFollowUp', $shell);
        $this->assertStringContainsString('validateSaleForm', $shell);
        $this->assertStringContainsString('collectSalePayload', $shell);
    }

    public function test_sale_cart_module_exists(): void
    {
        $cart = (string) file_get_contents(resource_path('js/mobile/sale-cart.js'));
        $seller = (string) file_get_contents(resource_path('js/seller-app.js'));

        $this->assertStringContainsString('collectCartItems', $cart);
        $this->assertStringContainsString('validateSaleForm', $cart);
        $this->assertStringContainsString('sale-cart.js', $seller);
    }

    public function test_point_create_status_optional_server_defaults_new(): void
    {
        $controller = (string) file_get_contents(base_path('app/Http/Controllers/Api/Mobile/V1/PointOpsController.php'));
        $service = (string) file_get_contents(base_path('app/Domains/Mobile/Services/MobileSellerOpsService.php'));

        $this->assertStringContainsString("'status' => ['nullable'", $controller);
        $this->assertStringContainsString('PropertyStatus::NEW', $controller);
        $this->assertStringContainsString('PropertyStatus::NEW', $service);
    }

    public function test_mobile_visit_request_allows_campaign_resolution(): void
    {
        $request = (string) file_get_contents(base_path('app/Domains/Mobile/Requests/MobilePointVisitRequest.php'));

        $this->assertStringContainsString("'campaign_id' =>", $request);
        $this->assertStringContainsString("'nullable'", $request);
    }

    public function test_map_visit_domain_services_preserved(): void
    {
        $adapter = (string) file_get_contents(resource_path('js/mobile/map-adapter.js'));
        $visitService = (string) file_get_contents(base_path('app/Domains/Visits/Services/VisitService.php'));

        $this->assertStringContainsString('renderMarkers', $adapter);
        $this->assertStringContainsString('recenterGps', $adapter);
        $this->assertStringContainsString('register(', $visitService);
        $this->assertStringContainsString('scheduleFollowUp', $visitService);
    }
}
