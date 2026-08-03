<?php

namespace App\Domains\Security\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Security\Enums\ConsentType;
use App\Domains\Security\Models\Consent;
use App\Domains\Security\Services\PrivacyService;
use Illuminate\Database\Eloquent\Model;

class RecordConsentAction
{
    public function __construct(
        protected PrivacyService $privacy,
    ) {}

    public function execute(
        Company $company,
        Model $subject,
        ConsentType|string $type,
        bool $granted = true,
        ?string $source = 'manual',
        ?User $actor = null,
    ): Consent {
        return $this->privacy->recordConsent(
            company: $company,
            subject: $subject,
            type: $type,
            granted: $granted,
            source: $source,
            actor: $actor,
        );
    }
}
