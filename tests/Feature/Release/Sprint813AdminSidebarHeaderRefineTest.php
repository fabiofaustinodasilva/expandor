<?php

namespace Tests\Feature\Release;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint813AdminSidebarHeaderRefineTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_platform_sidebar_uses_text_brand_without_logo_image(): void
    {
        $owner = $this->makePlatformAdmin();

        $html = $this->actingAs($owner)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('brand-mark', false)
            ->assertSee('Painel administrativo', false)
            ->assertDontSee('>Admin</span>', false)
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/id="platform-sidebar"[\s\S]*?<img[\s\S]*?<\/aside>/i',
            $html
        );
    }
}
