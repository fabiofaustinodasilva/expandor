<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\Plan;
use App\Domains\Company\Models\Subscription;
use App\Domains\Payments\Actions\ActivateSubscriptionAction;
use App\Domains\Payments\Actions\CancelSubscriptionAction;
use App\Domains\Payments\Actions\DowngradeSubscriptionAction;
use App\Domains\Payments\Actions\SuspendSubscriptionAction;
use App\Domains\Payments\Actions\UpgradeSubscriptionAction;
use App\Domains\Payments\DTOs\SubscriptionOverview;
use App\Domains\Payments\Repositories\PaymentRepository;

class SubscriptionService
{
    public function __construct(
        protected PaymentRepository $repository,
        protected ActivateSubscriptionAction $activateAction,
        protected SuspendSubscriptionAction $suspendAction,
        protected CancelSubscriptionAction $cancelAction,
        protected UpgradeSubscriptionAction $upgradeAction,
        protected DowngradeSubscriptionAction $downgradeAction,
    ) {}

    public function overview(Company $company): SubscriptionOverview
    {
        return new SubscriptionOverview(
            company: $company,
            subscription: $this->repository->activeSubscription($company),
            customer: $this->repository->customerForCompany($company),
            payments: $this->repository->paymentsForCompany($company),
            invoices: $this->repository->invoicesForCompany($company),
            paymentMethods: $this->repository->paymentMethodsForCompany($company),
        );
    }

    /**
     * @param  array<string, mixed>  $gatewayData
     */
    public function activate(Subscription $subscription, array $gatewayData = []): Subscription
    {
        return $this->activateAction->execute($subscription, $gatewayData);
    }

    public function suspend(Subscription $subscription): Subscription
    {
        return $this->suspendAction->execute($subscription);
    }

    public function cancel(Subscription $subscription, bool $suspendCompany = false): Subscription
    {
        return $this->cancelAction->execute($subscription, $suspendCompany);
    }

    public function upgrade(Subscription $subscription, Plan $plan): Subscription
    {
        return $this->upgradeAction->execute($subscription, $plan);
    }

    public function downgrade(Subscription $subscription, Plan $plan): Subscription
    {
        return $this->downgradeAction->execute($subscription, $plan);
    }
}
