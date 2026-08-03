<?php

namespace Tests\Feature\Branding;

use App\Domains\Branding\Models\Brand;
use App\Domains\Branding\Services\BrandingService;
use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Company\Models\Role;
use App\Support\CommercialTerminology;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class SaasBrandingSprint54Test extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_each_company_sees_only_its_logo_on_login_and_shell(): void
    {
        Storage::fake('public');

        $companyA = $this->makeCompanyWithPlan('Empresa Logo A');
        $companyB = $this->makeCompanyWithPlan('Empresa Logo B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'logo-a@saas.test',
        ]);
        $adminB = $this->makeUser($companyB, Role::ADMINISTRATOR, [
            'email' => 'logo-b@saas.test',
        ]);

        app(TenantContext::class)->set($companyA, $adminA);
        app(BrandingService::class)->store($companyA, [
            'system_name' => 'Sistema A',
            'display_name' => 'Marca Vermelha',
            'theme' => 'dark',
            'colors' => ['primary' => '#E53935', 'secondary' => '#111827', 'highlight' => '#F59E0B'],
        ], [
            'logo' => UploadedFile::fake()->image('logo-a.png', 120, 40),
        ]);

        app(TenantContext::class)->set($companyB, $adminB);
        app(BrandingService::class)->store($companyB, [
            'system_name' => 'Sistema B',
            'display_name' => 'Marca Azul',
            'theme' => 'dark',
            'colors' => ['primary' => '#3B82F6', 'secondary' => '#0F172A', 'highlight' => '#22C55E'],
        ], [
            'logo' => UploadedFile::fake()->image('logo-b.png', 120, 40),
        ]);

        $payloadA = app(BrandingService::class)->forCompany($companyA);
        $payloadB = app(BrandingService::class)->forCompany($companyB);

        $this->assertNotSame($payloadA->logoUrl, $payloadB->logoUrl);
        $this->assertSame('Marca Vermelha', $payloadA->displayName);
        $this->assertSame('Marca Azul', $payloadB->displayName);
        $this->assertSame('#E53935', $payloadA->primaryColor());
        $this->assertSame('#3B82F6', $payloadB->primaryColor());

        $this->actingAs($adminA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Marca Vermelha', false)
            ->assertDontSee('Marca Azul');

        $this->actingAs($adminB)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Marca Azul', false)
            ->assertDontSee('Marca Vermelha');
    }

    public function test_seller_cannot_edit_company_or_branding(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Permissão');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller-perm@saas.test',
        ]);

        $this->actingAs($seller)
            ->get(route('company.edit', $company))
            ->assertForbidden();

        $this->actingAs($seller)
            ->get(route('company.branding.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_update_company_segment_and_profile(): void
    {
        Storage::fake('public');

        $company = $this->makeCompanyWithPlan('Empresa Segmento');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-seg@saas.test',
            'phone' => '11999990000',
        ]);

        $this->actingAs($admin)
            ->put(route('company.update', $company), [
                'name' => 'Fantasia SaaS',
                'legal_name' => 'Fantasia SaaS LTDA',
                'document' => '12.345.678/0001-90',
                'email' => 'contato@fantasia.test',
                'phone' => '1133334444',
                'whatsapp' => '11988887777',
                'address' => 'Rua Comercial, 100',
                'segment' => CompanySegment::INTERNET->value,
            ])
            ->assertRedirect(route('company.show', $company));

        $company->refresh();
        $this->assertSame('Fantasia SaaS', $company->name);
        $this->assertSame(CompanySegment::INTERNET->value, $company->segment);
        $this->assertSame('11988887777', $company->whatsapp);

        $this->actingAs($admin)
            ->put(route('profile.update'), [
                'name' => 'Admin Atualizado',
                'phone' => '11911112222',
                'whatsapp' => '11933334444',
            ])
            ->assertRedirect(route('profile.edit'));

        $admin->refresh();
        $this->assertSame('Admin Atualizado', $admin->name);
        $this->assertSame('11933334444', $admin->whatsapp);
    }

    public function test_segment_changes_only_presentation_labels(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Termos');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-terms@saas.test',
        ]);

        $company->update(['segment' => CompanySegment::INTERNET->value]);
        $this->actingAs($admin);

        $this->assertSame('Plano', CommercialTerminology::offeringNoun());
        $this->assertSame('Instalação', CommercialTerminology::conversionNoun());
        $this->assertStringContainsString('Instalação', CommercialTerminology::saleCompleted());

        $company->update(['segment' => CompanySegment::DOOR_TO_DOOR->value]);
        $admin->unsetRelation('company');

        $this->assertSame('Produto', CommercialTerminology::offeringNoun());
        $this->assertSame('Venda', CommercialTerminology::conversionNoun());
        $this->assertSame('Venda realizada', CommercialTerminology::saleCompleted());
    }

    public function test_regression_map_customers_dashboard_commissions_still_open(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Regressão');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-reg@saas.test',
        ]);

        $this->actingAs($admin)
            ->get(route('map.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('commissions.index'))
            ->assertOk();
    }

    public function test_logo_mark_upload_is_persisted(): void
    {
        Storage::fake('public');

        $company = $this->makeCompanyWithPlan('Empresa Mark');
        $admin = $this->makeUser($company, Role::ADMINISTRATOR, [
            'email' => 'admin-mark@saas.test',
        ]);

        $this->actingAs($admin)
            ->post(route('company.branding.store'), [
                'system_name' => 'Mark CRM',
                'display_name' => 'Mark Brand',
                'theme' => 'dark',
                'colors' => [
                    'primary' => '#E53935',
                    'secondary' => '#111827',
                    'highlight' => '#F59E0B',
                    'accent' => '#E53935',
                    'bg' => '#0F1117',
                    'bg_elevated' => '#171A22',
                    'bg_soft' => '#1E2330',
                    'border' => '#2A3142',
                    'text' => '#F3F5F9',
                    'muted' => '#9AA3B5',
                    'accent_2' => '#F59E0B',
                    'success' => '#22C55E',
                    'warning' => '#F59E0B',
                ],
                'logo' => UploadedFile::fake()->image('logo.png', 120, 40),
                'logo_mark' => UploadedFile::fake()->image('mark.png', 64, 64),
            ])
            ->assertRedirect(route('company.branding.edit'));

        $brand = Brand::query()->withoutGlobalScopes()->where('company_id', $company->id)->first();
        $this->assertNotNull($brand?->logo_mark);
        Storage::disk('public')->assertExists($brand->logo_mark);
    }
}
