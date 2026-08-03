<?php

namespace App\Domains\AI\Context\Contracts;

use App\Domains\AI\DTOs\AIContextPayload;
use App\Domains\AI\Enums\AIContextType;

interface ContextBuilder
{
    public function type(): AIContextType;

    /**
     * @param  array<string, mixed>  $options
     */
    public function build(array $options = []): AIContextPayload;
}
