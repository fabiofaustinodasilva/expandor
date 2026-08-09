<?php

namespace Tests\Feature\Integrations;

use App\Domains\Company\Models\Role;
use App\Domains\Integrations\Enums\CompanyIntegrationStatus;
use App\Domains\Integrations\Models\CompanyIntegration;
use App\Domains\Integrations\Support\IntegrationProviders;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class Sprint8222GoogleMapsTestConnectionPostTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    private const KEY = 'AIzaSyTestConnectionKeyValue1234567890';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_edit_html_test_button_posts_without_put_spoof_hidden(): void
    {
        $company = $this->makeCompanyWithPlan('HTML Form Co', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $html = $this->actingAs($admin)
            ->get(route('operations.integrations.google-maps.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Testar conexão', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('formmethod="post"', $html);
        $this->assertStringContainsString('/operacao/integracoes/google-maps/test', $html);

        // No hidden method-spoof input; only the Save button carries _method=PUT.
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]+name=["\']_method["\'][^>]*>/i',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/<button[^>]+name="_method"[^>]+value="PUT"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/formaction="[^"]*google-maps\/test"/',
            $html
        );
    }

    public function test_post_test_connection_does_not_return_405(): void
    {
        $company = $this->makeCompanyWithPlan('POST Test Co', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        CompanyIntegration::query()->create([
            'company_id' => $company->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => self::KEY],
        ]);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'OK', 'results' => []], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('operations.integrations.google-maps.test'), [
                'browser_api_key' => '',
            ])
            ->assertRedirect(route('operations.integrations.google-maps.edit'))
            ->assertSessionHas('success');
    }

    public function test_get_test_connection_remains_405(): void
    {
        $company = $this->makeCompanyWithPlan('GET Test Co', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        $this->actingAs($admin)
            ->get(route('operations.integrations.google-maps.test'))
            ->assertStatus(405);
    }

    public function test_empty_key_uses_saved_credential(): void
    {
        $company = $this->makeCompanyWithPlan('Saved Key Co', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        CompanyIntegration::query()->create([
            'company_id' => $company->id,
            'provider' => IntegrationProviders::GOOGLE_MAPS,
            'category' => IntegrationProviders::CATEGORY_MAPS,
            'enabled' => true,
            'status' => CompanyIntegrationStatus::Connected,
            'credentials' => ['browser_api_key' => self::KEY],
        ]);

        Http::fake([
            'maps.googleapis.com/*' => Http::response(['status' => 'OK', 'results' => []], 200),
        ]);

        $this->actingAs($admin)
            ->from(route('operations.integrations.google-maps.edit'))
            ->post(route('operations.integrations.google-maps.test'), [
                // Empty = use encrypted credential on file.
            ])
            ->assertRedirect(route('operations.integrations.google-maps.edit'))
            ->assertSessionHas('success')
            ->assertSessionMissing('error');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'maps.googleapis.com')
                && ($request['key'] ?? null) === self::KEY;
        });
    }

    public function test_put_spoof_on_test_route_is_rejected_with_405(): void
    {
        $company = $this->makeCompanyWithPlan('PUT Spoof Co', 'enterprise');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR);
        app(TenantContext::class)->set($company, $admin);

        // Simulates the old bug: Test submit carried hidden _method=PUT.
        $this->actingAs($admin)
            ->post(route('operations.integrations.google-maps.test'), [
                '_method' => 'PUT',
                'browser_api_key' => self::KEY,
            ])
            ->assertStatus(405);
    }
}
