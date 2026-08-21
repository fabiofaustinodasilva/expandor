<?php

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Enums\PaymentMethodType;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use App\Domains\Payments\Providers\MercadoPagoProvider;
use App\Domains\Payments\Providers\ProviderFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class InvoicePaymentService
{
    public function __construct(
        protected ProviderFactory $providers,
        protected PaymentService $payments,
    ) {}

    /**
     * @return array{payment: Payment, transaction: ?PaymentGatewayTransaction, checkout_url: ?string, qr_code: ?string, qr_base64: ?string, boleto_url: ?string, digitable_line: ?string}
     */
    public function payWithPix(Invoice $invoice): array
    {
        return $this->createGatewayCharge($invoice, PaymentMethodType::Pix);
    }

    /**
     * @return array{payment: Payment, transaction: ?PaymentGatewayTransaction, checkout_url: ?string, qr_code: ?string, qr_base64: ?string, boleto_url: ?string, digitable_line: ?string}
     */
    public function payWithBoleto(Invoice $invoice): array
    {
        return $this->createGatewayCharge($invoice, PaymentMethodType::Boleto);
    }

    /**
     * Segunda via: reutiliza cobrança pendente válida do mesmo método.
     *
     * @return array{payment: Payment, transaction: ?PaymentGatewayTransaction, checkout_url: ?string, qr_code: ?string, qr_base64: ?string, boleto_url: ?string, digitable_line: ?string}|null
     */
    public function existingPendingCharge(Invoice $invoice, PaymentMethodType $method): ?array
    {
        $payment = Payment::query()
            ->withoutGlobalScopes()
            ->where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Pending)
            ->where('method', $method->value)
            ->latest('id')
            ->first();

        if ($payment === null) {
            return null;
        }

        $tx = PaymentGatewayTransaction::query()
            ->where('payment_id', $payment->gateway_payment_id)
            ->latest('id')
            ->first();

        return $this->present($payment, $tx);
    }

    /**
     * @return array{payment: Payment, transaction: ?PaymentGatewayTransaction, checkout_url: ?string, qr_code: ?string, qr_base64: ?string, boleto_url: ?string, digitable_line: ?string}
     */
    protected function createGatewayCharge(Invoice $invoice, PaymentMethodType $method): array
    {
        if (! $invoice->status->isPayable()) {
            throw new RuntimeException('Fatura não está disponível para pagamento.');
        }

        $reuse = $this->existingPendingCharge($invoice, $method);
        if ($reuse !== null) {
            return $reuse;
        }

        $provider = $this->providers->make('mercadopago');
        if (! $provider instanceof MercadoPagoProvider) {
            throw new RuntimeException('Mercado Pago indisponível.');
        }

        $amount = round((float) $invoice->amount_due, 2);
        $external = 'inv_'.$invoice->id.'_'.Str::lower(Str::random(10));
        $company = $invoice->company()->withoutGlobalScopes()->first()
            ?? \App\Domains\Company\Models\Company::query()->withoutGlobalScopes()->find($invoice->company_id);

        $data = [
            'amount' => $amount,
            'description' => 'Expandor — fatura '.$invoice->number,
            'buyer_email' => $company?->email,
            'buyer_name' => $company?->name,
            'checkout_uuid' => $external,
            'billing_type' => $method === PaymentMethodType::Pix ? 'PIX' : 'BOLETO',
            'success_url' => url('/company/financeiro/faturas/'.$invoice->id),
            'cancel_url' => url('/company/financeiro/faturas/'.$invoice->id),
        ];

        $result = $method === PaymentMethodType::Pix
            ? $provider->createPixPayment($data)
            : $provider->createBoletoPayment($data);

        return DB::transaction(function () use ($invoice, $method, $result, $amount, $external) {
            $payment = $this->payments->create([
                'company_id' => $invoice->company_id,
                'subscription_id' => $invoice->subscription_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'currency' => $invoice->currency ?: 'BRL',
                'status' => PaymentStatus::Pending,
                'method' => $method->value,
                'gateway' => 'mercadopago',
                'gateway_payment_id' => $result->gatewaySessionId,
                'raw' => [
                    'external_reference' => $external,
                    'flow' => $result->raw['flow'] ?? null,
                ],
            ]);

            $invoice->forceFill([
                'payment_method' => $method->value,
                'gateway_invoice_id' => $result->gatewaySessionId,
            ])->save();

            $tx = PaymentGatewayTransaction::query()->create([
                'payment_record_id' => $payment->id,
                'gateway' => 'mercadopago',
                'payment_method' => $method->value,
                'payment_id' => $result->gatewaySessionId,
                'status' => (string) ($result->raw['status'] ?? 'pending'),
                'pix_qr_code' => $result->raw['pix_qr_code'] ?? null,
                'pix_qr_code_base64' => $result->raw['pix_qr_code_base64'] ?? null,
                'pix_expiration_at' => $result->raw['pix_expiration_date'] ?? null,
                'raw' => array_merge($result->raw, ['external_reference' => $external]),
            ]);

            Log::info('billing.invoice_charge_created', [
                'invoice_id' => $invoice->id,
                'method' => $method->value,
                'gateway_payment_id' => $result->gatewaySessionId,
                'external_reference' => $external,
            ]);

            return $this->present($payment, $tx, $result->checkoutUrl);
        });
    }

    /**
     * @return array{payment: Payment, transaction: ?PaymentGatewayTransaction, checkout_url: ?string, qr_code: ?string, qr_base64: ?string, boleto_url: ?string, digitable_line: ?string}
     */
    protected function present(Payment $payment, ?PaymentGatewayTransaction $tx, ?string $checkoutUrl = null): array
    {
        $raw = $tx?->raw ?? [];

        return [
            'payment' => $payment,
            'transaction' => $tx,
            'checkout_url' => $checkoutUrl ?? ($raw['boleto_url'] ?? $raw['ticket_url'] ?? null),
            'qr_code' => $tx?->pix_qr_code,
            'qr_base64' => $tx?->pix_qr_code_base64,
            'boleto_url' => $raw['boleto_url'] ?? $raw['ticket_url'] ?? null,
            'digitable_line' => $raw['digitable_line'] ?? $raw['barcode'] ?? null,
        ];
    }
}
