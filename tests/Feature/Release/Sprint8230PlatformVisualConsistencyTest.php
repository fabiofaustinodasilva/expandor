<?php

namespace Tests\Feature\Release;

use App\Domains\Commissions\Enums\ProductCommissionType;
use App\Domains\Company\Models\Role;
use App\Domains\Maps\Enums\MapMarkerColor;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Support\AppTime;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

/**
 * Sprint 8.2.30 — visual consistency (tokens + primitives). No business-rule changes.
 */
class Sprint8230PlatformVisualConsistencyTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_design_tokens_exist(): void
    {
        $css = (string) file_get_contents(public_path('css/client-ui.css'));
        foreach ([
            '--color-primary',
            '--color-primary-hover',
            '--color-background',
            '--color-surface',
            '--color-surface-muted',
            '--color-border',
            '--color-text',
            '--color-text-muted',
            '--color-success',
            '--color-warning',
            '--color-danger',
            '--color-info',
            '--control-height',
            '--radius-control',
            '--radius-card',
            '--font-page-title',
            '--font-label',
        ] as $needle) {
            $this->assertStringContainsString($needle, $css);
        }
    }

    public function test_button_variants_exist(): void
    {
        $css = (string) file_get_contents(public_path('css/client-ui.css'));
        $layout = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $this->assertStringContainsString('.team-btn-primary', $css);
        $this->assertStringContainsString('.btn-icon', $css);
        $this->assertStringContainsString('.btn:focus-visible', $css);
        $this->assertStringContainsString('.btn:disabled', $css);
        $this->assertStringContainsString('.btn-primary', $layout);
        $this->assertStringContainsString('.btn-ghost', $layout);
        $this->assertStringContainsString('.btn-danger', $layout);
        $this->assertFileExists(resource_path('views/components/client/primary-button.blade.php'));
        $this->assertFileExists(resource_path('views/components/client/secondary-button.blade.php'));
        $this->assertFileExists(resource_path('views/components/client/danger-button.blade.php'));
    }

    public function test_form_controls_share_radius_and_focus(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $css = (string) file_get_contents(public_path('css/client-ui.css'));
        $this->assertStringContainsString('.form-control', $layout);
        $this->assertStringContainsString('border-radius: .75rem', $layout);
        $this->assertStringContainsString('.form-control:focus-visible', $css);
    }

    public function test_badge_tones_include_operational_set(): void
    {
        $badge = (string) file_get_contents(resource_path('views/components/client/status-badge.blade.php'));
        $layout = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        foreach (['success', 'warning', 'danger', 'info', 'primary'] as $tone) {
            $this->assertStringContainsString("'".$tone."'", $badge);
        }
        $this->assertStringContainsString('.badge-info', $layout);
        $this->assertStringContainsString('.badge-success', $layout);
    }

    public function test_card_primitives_exist(): void
    {
        $this->assertFileExists(resource_path('views/components/client/section-card.blade.php'));
        $this->assertFileExists(resource_path('views/components/client/metric-card.blade.php'));
        $css = (string) file_get_contents(public_path('css/client-ui.css'));
        $this->assertStringContainsString('.client-section-card', $css);
        $this->assertStringContainsString('.client-metric-card', $css);
    }

    public function test_table_responsive_primitives_exist(): void
    {
        $css = (string) file_get_contents(public_path('css/client-ui.css'));
        $this->assertStringContainsString('.client-data-table--responsive', $css);
        $this->assertStringContainsString('.table-num', $css);
        $this->assertStringContainsString('content: attr(data-label)', $css);
        $products = (string) file_get_contents(resource_path('views/commissions/products/index.blade.php'));
        $commissions = (string) file_get_contents(resource_path('views/commissions/index.blade.php'));
        $this->assertStringContainsString('client-data-table--responsive', $products);
        $this->assertStringContainsString('client-data-table--responsive', $commissions);
        $this->assertStringContainsString('table-num', $products);
        $this->assertStringContainsString('table-num', $commissions);
    }

    public function test_empty_state_primitive_and_copy(): void
    {
        $this->assertFileExists(resource_path('views/components/client/empty-state.blade.php'));
        $products = (string) file_get_contents(resource_path('views/commissions/products/index.blade.php'));
        $this->assertStringContainsString('Nenhum produto cadastrado', $products);
        $this->assertStringContainsString('Cadastrar produto', $products);
        $visits = (string) file_get_contents(resource_path('views/operations/my-visits.blade.php'));
        $this->assertStringContainsString('Você ainda não registrou visitas', $visits);
        $this->assertStringNotContainsString('próxima casa', $visits);
    }

    public function test_feedback_toast_and_alert_primitives(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $this->assertStringContainsString('op-toast', $layout);
        $this->assertStringContainsString('alert-success', $layout);
        $this->assertStringContainsString('alert-error', $layout);
        $this->assertFileExists(resource_path('views/components/client/alert.blade.php'));
        $maps = (string) file_get_contents(resource_path('views/maps/index.blade.php'));
        $this->assertStringContainsString('commission-reward', $maps);
        $this->assertStringContainsString('check-circle-2', $maps);
    }

    public function test_page_headers_on_key_screens(): void
    {
        foreach ([
            'views/dashboard/index.blade.php',
            'views/operations/team.blade.php',
            'views/commissions/products/index.blade.php',
            'views/operations/my-visits.blade.php',
            'views/operations/integrations.blade.php',
            'views/operations/integrations/google-maps.blade.php',
            'views/visits/follow-ups/index.blade.php',
            'views/customers/show.blade.php',
        ] as $rel) {
            $html = (string) file_get_contents(resource_path($rel));
            $this->assertStringContainsString('x-client.page-header', $html, $rel.' should use page-header');
        }
    }

    public function test_responsive_and_accessibility_primitives(): void
    {
        $css = (string) file_get_contents(public_path('css/client-ui.css'));
        $layout = (string) file_get_contents(resource_path('views/layouts/operational.blade.php'));
        $this->assertStringContainsString('--client-touch: 44px', $css);
        $this->assertStringContainsString(':focus-visible', $css);
        $this->assertStringContainsString('client-skip-link', $layout);
        $this->assertStringContainsString('width=device-width', $layout);
        $this->assertStringContainsString('@media (max-width: 640px)', $css);
    }

    public function test_glossary_copy_uses_ponto_on_map_drawer(): void
    {
        $maps = (string) file_get_contents(resource_path('views/maps/index.blade.php'));
        $this->assertStringContainsString('>Ponto</div>', $maps);
        $this->assertStringNotContainsString('📍 Residência', $maps);
        $this->assertStringNotContainsString('🏠', $maps);
    }

    public function test_seller_manager_admin_and_platform_layouts_share_language(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa 8230 Roles');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, ['email' => 'a-8230@test']);
        $manager = $this->makeUser($company, Role::MANAGER, ['email' => 'm-8230@test']);
        $seller = $this->makeUser($company, Role::SELLER, ['email' => 's-8230@test']);
        $platform = $this->makePlatformAdmin();

        app(TenantContext::class)->set($company, $admin);
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('client-page-header', false);
        $this->actingAs($admin)->get(route('commissions.products.index'))->assertOk()->assertSee('+ Novo produto');

        app(TenantContext::class)->set($company, $manager);
        $this->actingAs($manager)->get(route('operations.team'))->assertOk()->assertSee('client-page-header', false);

        app(TenantContext::class)->set($company, $seller);
        $this->actingAs($seller)->get(route('map.index'))->assertOk();
        $this->actingAs($seller)->get(route('operations.my-visits'))->assertOk()->assertSee('Histórico de atendimentos');

        $this->post(route('logout'));
        $login = $this->get(route('login'))->assertOk()->getContent();
        $this->assertStringContainsString('--primary:', $login);

        $this->actingAs($platform)->get(route('platform.dashboard'))->assertOk();
        $platformLayout = (string) file_get_contents(resource_path('views/layouts/platform.blade.php'));
        $this->assertStringContainsString('--color-primary', $platformLayout);
        $this->assertStringContainsString('client-ui.css', $platformLayout);
    }

    public function test_auth_and_error_pages_are_branded(): void
    {
        foreach (['403', '404', '419', '500'] as $code) {
            $path = resource_path('views/errors/'.$code.'.blade.php');
            $this->assertFileExists($path);
            $this->assertStringContainsString('errors.layout', (string) file_get_contents($path));
        }
        $layout = (string) file_get_contents(resource_path('views/errors/layout.blade.php'));
        $this->assertStringContainsString('Voltar ao início', $layout);
        $this->assertStringContainsString('var(--primary)', $layout);
        $this->assertFileExists(resource_path('views/auth/login.blade.php'));
        $this->assertFileExists(resource_path('views/auth/forgot-password.blade.php'));
        $this->assertFileExists(resource_path('views/auth/reset-password.blade.php'));
    }

    public function test_map_marker_colors_untouched(): void
    {
        $this->assertSame('#22c55e', MapMarkerColor::forStatus(PropertyStatus::CUSTOMER)->value);
        $this->assertSame('#ef4444', MapMarkerColor::forStatus(PropertyStatus::NEW)->value);
        $this->assertSame('#3b82f6', MapMarkerColor::forStatus(PropertyStatus::INTERESTED)->value);
        $this->assertSame('#f97316', MapMarkerColor::forStatus(PropertyStatus::RETURN_LATER)->value);
    }

    public function test_app_time_contract_untouched(): void
    {
        $this->assertSame('America/Sao_Paulo', AppTime::zone());
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertTrue(method_exists(AppTime::class, 'formatInstant'));
        $this->assertTrue(method_exists(AppTime::class, 'dayBoundsUtc'));
    }

    public function test_commission_rules_untouched(): void
    {
        $this->assertSame('fixed', ProductCommissionType::Fixed->value);
        $this->assertSame('percentage', ProductCommissionType::Percentage->value);
        $this->assertTrue(method_exists(Product::class, 'isFixedCommission'));
        $this->assertTrue(method_exists(Product::class, 'isPercentageCommission'));
        $this->assertSame(['fixed', 'percentage'], ProductCommissionType::values());
    }

    public function test_no_new_migrations_in_sprint(): void
    {
        $files = glob(base_path('database/migrations/*8230*')) ?: [];
        $this->assertSame([], $files);
    }

    public function test_team_presence_labels_differentiate_login_activity_visit(): void
    {
        $html = (string) file_get_contents(resource_path('views/operations/team.blade.php'));
        $this->assertStringContainsString('Último login', $html);
        $this->assertStringContainsString('Última atividade', $html);
        $this->assertStringContainsString('Última visita', $html);
        $this->assertStringContainsString('Login é o acesso à conta', $html);
    }
}
