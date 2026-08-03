<?php

namespace App\Domains\Payments\Services;

use App\Domains\Company\Models\Company;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Events\PaymentConfirmed;
use App\Domains\Payments\Events\PaymentCreated;
use App\Domains\Payments\Events\PaymentFailed;
use App\Domains\Payments\Models\CheckoutSession;
use App\Domains\Payments\Models\Customer;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Repositories\PaymentRepository;

class PaymentService
{
    public function __construct(
        protected PaymentRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Payment
    {
        $payment = Payment::query()->withoutGlobalScopes()->create($attributes);

        PaymentCreated::dispatch($payment);

        return $payment;
    }

    public function markPaid(Payment $payment): Payment
    {
        $payment->forceFill([
            'status' => PaymentStatus::Paid,
            'paid_at' => $payment->paid_at ?? now(),
            'failure_reason' => null,
        ])->save();

        PaymentConfirmed::dispatch($payment);

        return $payment->fresh();
    }

    public function markFailed(Payment $payment, ?string $reason = null): Payment
    {
        $payment->forceFill([
            'status' => PaymentStatus::Failed,
            'failure_reason' => $reason,
        ])->save();

        PaymentFailed::dispatch($payment);

        return $payment->fresh();
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    public function recordCheckoutPayment(
        CheckoutSession $checkout,
        Customer $customer,
        ?Company $company,
        ?Invoice $invoice,
        ?string $gatewayPaymentId,
        ?string $method,
        array $raw = [],
    ): Payment {
        if ($gatewayPaymentId) {
            $existing = $this->repository->findPaymentByGateway($checkout->gateway, $gatewayPaymentId);
            if ($existing !== null) {
                $existing->forceFill([
                    'company_id' => $company?->id ?? $existing->company_id,
                    'invoice_id' => $invoice?->id ?? $existing->invoice_id,
                    'method' => $method ?? $existing->method,
                    'raw' => $raw ?: $existing->raw,
                ])->save();

                if ($existing->status !== PaymentStatus::Paid) {
                    return $this->markPaid($existing->fresh());
                }

                return $existing->fresh();
            }
        }

        $pending = Payment::query()
            ->withoutGlobalScopes()
            ->where('checkout_session_id', $checkout->id)
            ->where('status', PaymentStatus::Pending)
            ->latest('id')
            ->first();

        if ($pending !== null) {
            $pending->forceFill([
                'company_id' => $company?->id,
                'customer_id' => $customer->id,
                'subscription_id' => $company
                    ? $this->repository->activeSubscription($company)?->id
                    : null,
                'invoice_id' => $invoice?->id,
                'gateway_payment_id' => $gatewayPaymentId ?? $pending->gateway_payment_id ?? ('local_'.$checkout->uuid),
                'method' => $method ?? $pending->method,
                'raw' => $raw ?: $pending->raw,
            ])->save();

            return $this->markPaid($pending->fresh());
        }

        $payment = $this->create([
            'company_id' => $company?->id,
            'customer_id' => $customer->id,
            'subscription_id' => $company
                ? $this->repository->activeSubscription($company)?->id
                : null,
            'invoice_id' => $invoice?->id,
            'checkout_session_id' => $checkout->id,
            'amount' => $checkout->amount,
            'currency' => $checkout->currency,
            'status' => PaymentStatus::Paid,
            'method' => $method,
            'gateway' => $checkout->gateway,
            'gateway_payment_id' => $gatewayPaymentId ?? ('local_'.$checkout->uuid),
            'paid_at' => now(),
            'raw' => $raw,
        ]);

        PaymentConfirmed::dispatch($payment);

        return $payment;
    }
}
