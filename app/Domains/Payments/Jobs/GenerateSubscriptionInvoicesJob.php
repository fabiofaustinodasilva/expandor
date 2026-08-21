<?php

namespace App\Domains\Payments\Jobs;

use App\Domains\Payments\Services\RecurringBillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSubscriptionInvoicesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(RecurringBillingService $service): void
    {
        $service->generateDueInvoices();
        $service->markOverdueInvoices();
    }
}
