<?php

namespace App\Domains\Platform\Activation\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Activation\Channels\EmailActivationChannel;
use App\Domains\Platform\Activation\Channels\InternalNotificationChannel;
use App\Domains\Platform\Activation\Channels\WhatsAppActivationChannel;
use App\Domains\Platform\Activation\Contracts\ActivationOutreachChannel;
use App\Domains\Platform\Activation\DTOs\ActivationOutreachMessage;

class ActivationOutreachService
{
    /** @var array<string, ActivationOutreachChannel> */
    protected array $channels = [];

    public function __construct(
        EmailActivationChannel $email,
        WhatsAppActivationChannel $whatsapp,
        InternalNotificationChannel $internal,
    ) {
        foreach ([$email, $whatsapp, $internal] as $channel) {
            $this->channels[$channel->key()] = $channel;
        }
    }

    /**
     * @param  list<string>|null  $channelKeys
     * @return array<string, bool>
     */
    public function dispatch(Company $company, ActivationOutreachMessage $message, ?User $actor = null, ?array $channelKeys = null): array
    {
        $keys = $channelKeys ?? array_keys($this->channels);
        $results = [];

        foreach ($keys as $key) {
            $channel = $this->channels[$key] ?? null;
            if ($channel === null) {
                continue;
            }
            $results[$key] = $channel->send($company, $message, $actor);
        }

        return $results;
    }

    public function nudgeFirstCustomer(Company $company, ?User $actor = null): array
    {
        return $this->dispatch($company, new ActivationOutreachMessage(
            template: 'activation.nudge_first_customer',
            subject: 'Cadastre seu primeiro cliente',
            body: 'Você ainda não cadastrou seu primeiro cliente. Clique aqui para continuar.',
            ctaUrl: route('onboarding.customer'),
            metadata: ['step' => 'customer'],
        ), $actor);
    }

    /**
     * @return list<string>
     */
    public function availableChannels(): array
    {
        return array_keys($this->channels);
    }
}
