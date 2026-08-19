<?php

namespace Tests\Feature\Auth;

use App\Domains\Company\Models\Role;
use App\Domains\Platform\Services\PlatformBrandingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 5.5.2/5.5.3 — login aponta para /cadastro (form Trial real).
 */
class LoginPreparationSprint552Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
        Storage::fake('public');
    }

    public function test_guest_sees_trial_cta_pointing_to_cadastro(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Acesse sua conta', false)
            ->assertSee('Ainda não conhece o Expandor?', false)
            ->assertSee('Começar agora', false)
            ->assertSee('data-trial-cta="1"', false)
            ->assertSee('/cadastro', false);

        $this->get(route('signup.create'))
            ->assertRedirect(route('marketplace.home').'#demo');
    }

    public function test_authenticated_user_does_not_see_login_trial_button(): void
    {
        $company = $this->makeCompanyWithPlan('Cliente Beta');
        $user = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'beta@tenant.test',
        ]);

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect();

        $html = view('auth.login', [
            'brand' => app(PlatformBrandingService::class)->payload(),
            'errors' => new \Illuminate\Support\ViewErrorBag([]),
        ])->render();

        $this->assertStringNotContainsString('data-trial-cta="1"', $html);
        $this->assertStringNotContainsString('Começar agora', $html);
    }

    public function test_custom_branding_works_on_login_and_cadastro(): void
    {
        $slogan = 'MAPEIE - ABORDE - REGISTRE - ANALISE - VENDA';

        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor Pro',
            'slogan' => $slogan,
            'primary_color' => '#0EA5E9',
            'secondary_color' => '#0F172A',
            'highlight_color' => '#F97316',
        ], [
            'logo' => UploadedFile::fake()->image('logo.png', 220, 80),
        ]);

        $payload = app(PlatformBrandingService::class)->payload();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor Pro', false)
            ->assertSee('Ainda não conhece o Expandor Pro?', false)
            ->assertSee($slogan, false)
            ->assertSee($payload->logoUrl, false)
            ->assertSee('btn-trial', false);

        $this->get(route('signup.create'))
            ->assertRedirect(route('marketplace.home').'#demo');
    }

    public function test_empty_slogan_does_not_break_login_preparation(): void
    {
        app(PlatformBrandingService::class)->update([
            'name' => 'Expandor',
            'slogan' => '',
            'primary_color' => '#3B82F6',
            'secondary_color' => '#171A22',
            'highlight_color' => '#EF4444',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('Acesse sua conta', false)
            ->assertSee('Começar agora', false)
            ->assertDontSee('data-platform-slogan="1"', false);

        $this->get(route('signup.create'))
            ->assertRedirect(route('marketplace.home').'#demo');
    }

    public function test_expandor_fallback_works_without_platform_brand(): void
    {
        $this->assertDatabaseCount('platform_brands', 0);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Login — Expandor', false)
            ->assertSee('data-platform-fallback="1"', false)
            ->assertSee('Bem-vindo ao Expandor', false)
            ->assertSee('Começar agora', false);

        $this->get('/cadastro')
            ->assertRedirect(route('marketplace.home').'#demo');
    }
}
