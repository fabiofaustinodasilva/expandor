<?php

namespace Tests\Feature\Training;

use App\Domains\Company\Models\Role;
use App\Domains\Training\Enums\TrainingContentType;
use App\Domains\Training\Models\TrainingCategory;
use App\Domains\Training\Models\TrainingContent;
use App\Domains\Training\Models\TrainingProgress;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class TrainingModuleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedFoundation();
    }

    public function test_company_cannot_access_another_company_training(): void
    {
        $companyA = $this->makeCompanyWithPlan('Empresa Academia A');
        $companyB = $this->makeCompanyWithPlan('Empresa Academia B');

        $adminA = $this->makeUser($companyA, Role::ADMINISTRATOR, [
            'email' => 'admin-a@training.test',
        ]);

        $categoryA = TrainingCategory::factory()->create([
            'company_id' => $companyA->id,
            'name' => 'Categoria Alpha Unica',
        ]);

        TrainingContent::factory()->create([
            'company_id' => $companyA->id,
            'category_id' => $categoryA->id,
            'title' => 'Conteudo Alpha Unico',
        ]);

        $categoryB = TrainingCategory::factory()->create([
            'company_id' => $companyB->id,
            'name' => 'Categoria Beta Unica',
        ]);

        TrainingContent::factory()->create([
            'company_id' => $companyB->id,
            'category_id' => $categoryB->id,
            'title' => 'Conteudo Beta Unico',
        ]);

        $this->actingAs($adminA)
            ->get(route('training.categories.index'))
            ->assertOk()
            ->assertSee('Categoria Alpha Unica')
            ->assertDontSee('Categoria Beta Unica');

        $this->actingAs($adminA)
            ->get(route('training.contents.index'))
            ->assertOk()
            ->assertSee('Conteudo Alpha Unico')
            ->assertDontSee('Conteudo Beta Unico');

        $foreign = TrainingContent::withoutGlobalScopes()
            ->where('company_id', $companyB->id)
            ->firstOrFail();

        $this->actingAs($adminA)
            ->get(route('training.contents.edit', $foreign))
            ->assertNotFound();
    }

    public function test_seller_can_access_allowed_contents(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Academia Seller');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'seller@training.test',
        ]);

        $category = TrainingCategory::factory()->create([
            'company_id' => $company->id,
            'active' => true,
            'name' => 'Script de Abordagem',
        ]);

        $active = TrainingContent::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Como abordar o morador',
            'type' => TrainingContentType::TEXT,
            'content' => 'Passo a passo da abordagem.',
            'active' => true,
        ]);

        TrainingContent::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Conteudo Inativo Oculto',
            'active' => false,
        ]);

        $this->actingAs($seller)
            ->get(route('sales-app.training.index'))
            ->assertOk()
            ->assertSee('Como abordar o morador')
            ->assertDontSee('Conteudo Inativo Oculto');

        $this->actingAs($seller)
            ->get(route('sales-app.training.show', $active))
            ->assertOk()
            ->assertSee('Passo a passo da abordagem.');

        $this->assertDatabaseHas('training_progress', [
            'user_id' => $seller->id,
            'training_content_id' => $active->id,
        ]);
    }

    public function test_progress_is_saved_when_completed(): void
    {
        $company = $this->makeCompanyWithPlan('Empresa Progresso');
        $seller = $this->makeUser($company, Role::SELLER, [
            'email' => 'progress@training.test',
        ]);

        app(TenantContext::class)->set($company, $seller);

        $category = TrainingCategory::factory()->create([
            'company_id' => $company->id,
            'active' => true,
        ]);

        $content = TrainingContent::factory()->create([
            'company_id' => $company->id,
            'category_id' => $category->id,
            'title' => 'Técnicas de fechamento',
            'type' => TrainingContentType::TEXT,
            'content' => 'Material de fechamento.',
            'active' => true,
        ]);

        $this->actingAs($seller)
            ->post(route('sales-app.training.complete', $content))
            ->assertRedirect(route('sales-app.training.show', $content));

        $progress = TrainingProgress::query()
            ->where('user_id', $seller->id)
            ->where('training_content_id', $content->id)
            ->firstOrFail();

        $this->assertNotNull($progress->started_at);
        $this->assertNotNull($progress->completed_at);
        $this->assertSame($company->id, $progress->company_id);
    }
}
