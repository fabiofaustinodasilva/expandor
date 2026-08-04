<?php

namespace App\Domains\Platform\Activation\Channels;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Activation\Contracts\ActivationOutreachChannel;
use App\Domains\Platform\Activation\DTOs\ActivationOutreachMessage;
use Illuminate\Support\Facades\Log;

/**
 * Stub preparado para WhatsApp de customer success (Sprint futura).
 */
class WhatsAppActivationChannel implements ActivationOutreachChannel
{
    public function key(): string
    {
        return 'whatsapp';
    }

    public function send(Company $company, ActivationOutreachMessage $message, ?User $actor = null): bool
    {
        Log::info('activation.outreach.whatsapp.stub', [
            'company_id' => $company->id,
            'template' => $message->template,
            'cta' => $message->ctaUrl,
        ]);

        return true;
    }
}
