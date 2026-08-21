<?php

namespace App\Domains\Payments\Jobs;

use App\Domains\Payments\Services\EnforceBillingDelinquencyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnforceBillingDelinquencyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(EnforceBillingDelinquencyService $service): void
    {
        $service->run();
    }
}
