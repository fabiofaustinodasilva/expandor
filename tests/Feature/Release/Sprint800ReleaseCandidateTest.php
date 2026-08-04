<?php

namespace Tests\Feature\Release;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint800ReleaseCandidateTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_marketplace_home_includes_mobile_menu_toggle(): void
    {
        $this->get(route('marketplace.home'))
            ->assertOk()
            ->assertSee('mkp-menu-toggle', false)
            ->assertSee('id="mkp-nav"', false);
    }

    public function test_platform_layout_includes_ux_polish(): void
    {
        $owner = $this->makePlatformAdmin();

        $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('ux-toast-host', false)
            ->assertSee('ExpandorUX', false);
    }

    public function test_plans_page_renders_without_full_landing_assemble_dependency(): void
    {
        $this->get(route('marketplace.plans'))
            ->assertOk()
            ->assertSee('Planos', false);
    }
}
