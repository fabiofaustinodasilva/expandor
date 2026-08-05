<?php

namespace Tests\Feature\Release;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint814PremiumAdminExperienceTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_platform_dashboard_uses_page_header_and_metrics(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('page-header', false)
            ->assertSee('breadcrumb', false)
            ->assertSee('metric-card', false)
            ->assertSee('Painel Expandor', false)
            ->assertSee('Saúde dos clientes', false)
            ->assertDontSee('Customer Health & Console', false);
    }

    public function test_companies_index_uses_standardized_header_and_filters(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.companies.index'))
            ->assertOk()
            ->assertSee('page-header', false)
            ->assertSee('filter-bar', false)
            ->assertSee('Nova empresa', false)
            ->assertSee('Ativa', false);
    }

    public function test_design_system_exposes_alert_and_page_header_styles(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('alert-warning', false)
            ->assertSee('alert-info', false)
            ->assertSee('.page-header', false)
            ->assertSee('.metric-card', false);
    }
}
