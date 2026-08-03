<?php

namespace App\Domains\Communication\Services;

use App\Domains\Billing\Enums\UsageMetric;
use App\Domains\Billing\Exceptions\PlanLimitExceededException;
use App\Domains\Billing\Services\BillingService;
use App\Domains\Communication\Enums\MessageDirection;
use App\Domains\Communication\Enums\MessageStatus;
use App\Domains\Communication\Enums\MessageTemplateEvent;
use App\Domains\Communication\Events\MessageQueuedForDelivery;
use App\Domains\Communication\Events\MessageRegistered;
use App\Domains\Communication\Models\Message;
use App\Domains\Communication\Models\MessageTemplate;
use App\Domains\Communication\Templates\MessageTemplateRenderer;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Residents\Models\Resident;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MessageService
{
    public function __construct(
        protected MessageTemplateRenderer $renderer,
        protected BillingService $billing,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(array $data, ?User $actor = null): Message
    {
        return DB::transaction(function () use ($data, $actor) {
            /** @var Resident $resident */
            $resident = Resident::query()->findOrFail($data['resident_id']);

            $direction = MessageDirection::from($data['direction'] ?? MessageDirection::OUTBOUND->value);
            $status = MessageStatus::from(
                $data['status'] ?? (
                    $direction === MessageDirection::INBOUND
                        ? MessageStatus::RECEIVED->value
                        : MessageStatus::PENDING->value
                )
            );

            if ($direction === MessageDirection::OUTBOUND) {
                try {
                    $this->billing->assertWithinLimit(UsageMetric::MESSAGES, 1, $actor?->company);
                } catch (PlanLimitExceededException $exception) {
                    throw ValidationException::withMessages([
                        'message' => [$exception->getMessage()],
                    ]);
                }
            }

            $message = Message::query()->create([
                'resident_id' => $resident->id,
                'user_id' => $data['user_id'] ?? $actor?->id,
                'direction' => $direction,
                'message' => $data['message'],
                'status' => $status,
                'sent_at' => $data['sent_at'] ?? (
                    $direction === MessageDirection::INBOUND ? now() : null
                ),
            ]);

            if ($direction === MessageDirection::OUTBOUND) {
                $this->billing->registerConsumption(
                    UsageMetric::MESSAGES,
                    1,
                    company: $actor?->company ?? $message->company,
                );
            }

            MessageRegistered::dispatch($message);

            return $message->load(['resident', 'user']);
        });
    }

    /**
     * Register outbound message and queue for future provider delivery.
     *
     * @param  array<string, mixed>  $data
     */
    public function queueOutbound(array $data, ?User $actor = null): Message
    {
        $message = $this->register(array_merge($data, [
            'direction' => MessageDirection::OUTBOUND->value,
            'status' => MessageStatus::QUEUED->value,
        ]), $actor);

        MessageQueuedForDelivery::dispatch($message);

        return $message->refresh();
    }

    /**
     * Build message body from template and queue outbound delivery.
     *
     * @param  array<string, string|null>  $extra
     */
    public function queueFromTemplate(
        Resident $resident,
        MessageTemplate $template,
        ?User $actor = null,
        array $extra = []
    ): Message {
        if (! $template->active) {
            throw ValidationException::withMessages([
                'template' => 'O template selecionado está inativo.',
            ]);
        }

        $body = $this->renderer->render($template, $resident, $extra);

        return $this->queueOutbound([
            'resident_id' => $resident->id,
            'message' => $body,
        ], $actor);
    }

    public function findActiveTemplateByEvent(MessageTemplateEvent $event): ?MessageTemplate
    {
        return MessageTemplate::query()
            ->where('event', $event->value)
            ->where('active', true)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTemplate(array $data): MessageTemplate
    {
        return MessageTemplate::query()->create([
            'name' => $data['name'],
            'event' => MessageTemplateEvent::from($data['event']),
            'content' => $data['content'],
            'active' => (bool) ($data['active'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTemplate(MessageTemplate $template, array $data): MessageTemplate
    {
        $template->update([
            'name' => $data['name'],
            'event' => MessageTemplateEvent::from($data['event']),
            'content' => $data['content'],
            'active' => array_key_exists('active', $data)
                ? (bool) $data['active']
                : $template->active,
        ]);

        return $template->refresh();
    }
}
