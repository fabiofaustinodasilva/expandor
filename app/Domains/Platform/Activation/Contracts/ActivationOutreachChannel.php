<?php

namespace App\Domains\Platform\Activation\Contracts;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\User;
use App\Domains\Platform\Activation\DTOs\ActivationOutreachMessage;

interface ActivationOutreachChannel
{
    public function key(): string;

    public function send(Company $company, ActivationOutreachMessage $message, ?User $actor = null): bool;
}
