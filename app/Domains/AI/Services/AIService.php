<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\Context\ContextBuilderFactory;
use App\Domains\AI\DTOs\AIAnalyzeResult;
use App\Domains\AI\DTOs\AIChatResult;
use App\Domains\AI\Enums\AIContextType;
use App\Domains\AI\Models\AIConversation;
use App\Domains\AI\Providers\ProviderFactory;
use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Services\BillingService;
use App\Domains\Company\Models\User;
use App\Tenancy\TenantContext;
use RuntimeException;

class AIService
{
    public function __construct(
        protected ProviderFactory $providers,
        protected ContextBuilderFactory $contexts,
        protected TenantContext $tenant,
        protected BillingService $billing,
    ) {}

    /**
     * Assistive chat: builds tenant context, calls provider, persists history.
     * Never mutates CRM commercial data.
     *
     * @param  array<string, mixed>  $options
     */
    public function ask(
        string $question,
        AIContextType|string $contextType,
        User $user,
        array $options = [],
    ): AIConversation {
        $this->assertSameTenant($user);

        $context = $this->contexts->make($contextType)->build($options);
        $provider = $this->providers->make($options['provider'] ?? null);

        $systemPrompt = $this->systemPrompt($context->body);
        $result = $provider->chat($systemPrompt, $question, $options);

        return $this->record(
            user: $user,
            contextType: $context->type,
            question: $question,
            result: $result,
        );
    }

    /**
     * Assistive analysis over the current tenant context.
     *
     * @param  array<string, mixed>  $options
     */
    public function analyze(
        AIContextType|string $contextType,
        User $user,
        string $focus = 'Gere insights comerciais sugestivos.',
        array $options = [],
    ): AIConversation {
        $this->assertSameTenant($user);

        $context = $this->contexts->make($contextType)->build($options);
        $provider = $this->providers->make($options['provider'] ?? null);

        $systemPrompt = $this->systemPrompt($context->body);
        $analysis = $provider->analyze($systemPrompt, $context->body."\n\nFoco: ".$focus, $options);

        $chatResult = $analysis->success
            ? AIChatResult::ok(
                content: $analysis->summary,
                tokensUsed: $analysis->tokensUsed,
                provider: $analysis->provider,
                raw: $analysis->raw,
            )
            : AIChatResult::fail(
                $analysis->error ?? 'Analyze failed.',
                $analysis->provider,
                $analysis->raw,
            );

        return $this->record(
            user: $user,
            contextType: $context->type,
            question: $focus,
            result: $chatResult,
        );
    }

    /**
     * Expose raw provider chat without persisting (for jobs/tests).
     *
     * @param  array<string, mixed>  $options
     */
    public function chatWithProvider(
        string $systemPrompt,
        string $userMessage,
        array $options = [],
    ): AIChatResult {
        return $this->providers->make($options['provider'] ?? null)
            ->chat($systemPrompt, $userMessage, $options);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function analyzeWithProvider(
        string $systemPrompt,
        string $payload,
        array $options = [],
    ): AIAnalyzeResult {
        return $this->providers->make($options['provider'] ?? null)
            ->analyze($systemPrompt, $payload, $options);
    }

    protected function record(
        User $user,
        AIContextType $contextType,
        string $question,
        AIChatResult $result,
    ): AIConversation {
        $companyId = $this->tenant->id() ?? $user->company_id;

        if ($companyId === null) {
            throw new RuntimeException('Company context is required to record AI conversations.');
        }

        $answer = $result->success
            ? $result->content
            : 'Não foi possível obter sugestão da IA: '.($result->error ?? 'erro desconhecido');

        $conversation = AIConversation::query()->create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'context_type' => $contextType,
            'question' => $question,
            'answer' => $answer,
            'provider' => $result->provider,
            'tokens_used' => $result->tokensUsed,
        ]);

        if (($result->tokensUsed ?? 0) > 0) {
            $this->billing->registerConsumption(
                UsageMetric::AI_TOKENS,
                (int) $result->tokensUsed,
                company: $user->company,
            );
        }

        return $conversation;
    }

    protected function systemPrompt(string $contextBody): string
    {
        return trim((string) config('ai.system_preamble'))."\n\n".$contextBody;
    }

    protected function assertSameTenant(User $user): void
    {
        $tenantId = $this->tenant->id();

        if ($tenantId !== null && (int) $user->company_id !== (int) $tenantId) {
            throw new RuntimeException('User does not belong to the current tenant.');
        }
    }
}
