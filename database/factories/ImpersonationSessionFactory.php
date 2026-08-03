<?php

namespace Database\Factories;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Models\ImpersonationSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ImpersonationSession> */
class ImpersonationSessionFactory extends Factory
{
    protected $model = ImpersonationSession::class;

    public function definition(): array
    {
        return [
            'platform_admin_id' => User::factory(),
            'target_user_id' => User::factory(),
            'target_company_id' => Company::factory(),
            'reason' => 'Suporte',
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'started_at' => now(),
            'ended_at' => null,
        ];
    }
}
