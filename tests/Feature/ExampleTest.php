<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesTenantUsers;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use CreatesTenantUsers;
    use RefreshDatabase;

    public function test_the_application_serves_marketplace_home(): void
    {
        $this->seedFoundation();

        $this->get('/')
            ->assertOk()
            ->assertSee('Expandor');
    }
}
