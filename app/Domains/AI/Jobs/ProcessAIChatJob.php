<?php

namespace App\Domains\AI\Jobs;

use App\Domains\AI\Enums\AIContextType;
use App\Domains\AI\Services\AIService;
use App\Domains\Company\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Optional async assistive chat. Still only persists suggestions — no CRM writes.
 */
class ProcessAIChatJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $userId,
        public string $question,
        public string $contextType,
    ) {}

    public function handle(AIService $ai, TenantContext $tenant): void
    {
        $user = User::query()->find($this->userId);

        if ($user === null) {
            return;
        }

        $tenant->set($user->company, $user);

        $ai->ask(
            question: $this->question,
            contextType: AIContextType::from($this->contextType),
            user: $user,
        );
    }
}
