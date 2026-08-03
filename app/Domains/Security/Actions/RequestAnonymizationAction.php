<?php

namespace App\Domains\Security\Actions;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Security\Models\AnonymizationRequest;
use App\Domains\Security\Services\PrivacyService;
use Illuminate\Database\Eloquent\Model;

class RequestAnonymizationAction
{
    public function __construct(
        protected PrivacyService $privacy,
    ) {}

    public function execute(
        Company $company,
        User $actor,
        Model $subject,
        ?string $reason = null,
    ): AnonymizationRequest {
        return $this->privacy->requestAnonymization($company, $actor, $subject, $reason);
    }
}
