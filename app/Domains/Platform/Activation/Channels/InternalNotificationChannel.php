<?php

namespace App\Domains\Platform\Activation\Channels;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Activation\Contracts\ActivationOutreachChannel;
use App\Domains\Platform\Activation\DTOs\ActivationOutreachMessage;
use App\Domains\Security\Services\SecurityService;

/**
 * Stub de notificação interna (auditoria) — pronto para UI de inbox futura.
 */
class InternalNotificationChannel implements ActivationOutreachChannel
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    public function key(): string
    {
        return 'internal';
    }

    public function send(Company $company, ActivationOutreachMessage $message, ?User $actor = null): bool
    {
        $this->security->recordAudit(
            action: 'activation.outreach.internal',
            user: $actor,
            auditable: $company,
            newValues: [
                'template' => $message->template,
                'subject' => $message->subject,
                'body' => $message->body,
                'cta_url' => $message->ctaUrl,
            ],
            companyId: $company->id,
        );

        return true;
    }
}
