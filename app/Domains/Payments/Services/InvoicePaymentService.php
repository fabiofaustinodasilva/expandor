<?php

namespace App\Domains\Payments\Services;

use App\Domains\Payments\Exceptions\PaymentGatewayClientException;
use App\Domains\Payments\Enums\PaymentMethodType;
use App\Domains\Payments\Enums\PaymentStatus;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentGatewayTransaction;
use App\Domains\Payments\Providers\DTOs\GatewayCheckoutResult;
use App\Domains\Payments\Providers\MercadoPagoProvider;
use App\Domains\Payments\Providers\ProviderFactory;
use Illuminate\Http\Client\RequestException;
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

        $amount = round((float) $invoice->amount_due, 2);
        $external = 'inv_'.$invoice->id.'_'.Str::lower(Str::random(10));
        $company = $invoice->company()->withoutGlobalScopes()->first()
            ?? \App\Domains\Company\Models\Company::query()->withoutGlobalScopes()->find($invoice->company_id);

        $payerEmail = $this->resolvePayerEmail($company);

        $token = (string) config('payments.providers.mercadopago.access_token', '');
        $allowDemo = $token === '' && (app()->environment('local', 'testing') || (bool) config('payments.allow_fake'));

        if ($allowDemo) {
            $result = $this->demoChargeResult($method, $external, $amount);
        } else {
            $provider = $this->providers->make('mercadopago');
            if (! $provider instanceof MercadoPagoProvider) {
                throw new RuntimeException('Mercado Pago indisponível.');
            }

            $data = [
                'amount' => $amount,
                'description' => 'Expandor — fatura '.$invoice->number,
                'buyer_email' => $payerEmail,
                'buyer_name' => $company?->name,
                'checkout_uuid' => $external,
                'billing_type' => $method === PaymentMethodType::Pix ? 'PIX' : 'BOLETO',
                'success_url' => url('/company/financeiro/faturas/'.$invoice->id),
                'cancel_url' => url('/company/financeiro/faturas/'.$invoice->id),
            ];

            try {
                $result = $method === PaymentMethodType::Pix
                    ? $provider->createPixPayment($data)
                    : $provider->createBoletoPayment($data);
            } catch (RequestException $e) {
                $this->throwGatewayClientError($e, $method);
            }
        }

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

    protected function resolvePayerEmail(?\App\Domains\Company\Models\Company $company): string
    {
        $candidates = array_filter([
            $company?->email,
            $company?->users()
                ->withoutGlobalScopes()
                ->whereHas('role', fn ($q) => $q->where('slug', \App\Domains\Company\Models\Role::ADMINISTRATOR))
                ->value('email'),
        ]);

        foreach ($candidates as $email) {
            $normalized = strtolower(trim((string) $email));
            if (filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                return $normalized;
            }
        }

        throw new PaymentGatewayClientException(
            'Não foi possível gerar a cobrança. Verifique o e-mail de cobrança da empresa ou entre em contato com o suporte.',
            'Invalid payer email for company '.$company?->id,
        );
    }

    protected function throwGatewayClientError(RequestException $exception, PaymentMethodType $method): void
    {
        $response = $exception->response;
        $body = $response?->json() ?? [];
        $message = (string) (data_get($body, 'message') ?? $exception->getMessage());
        $causes = data_get($body, 'cause', []);

        Log::warning('billing.gateway_client_error', [
            'method' => $method->value,
            'status' => $response?->status(),
            'message' => $message,
            'cause' => $causes,
        ]);

        $userMessage = 'Não foi possível gerar o '
            .($method === PaymentMethodType::Pix ? 'PIX' : 'boleto')
            .'. Verifique os dados de cobrança da empresa ou entre em contato com o suporte.';

        throw new PaymentGatewayClientException($userMessage, $message, is_array($causes) ? $causes : null);
    }

    /**
     * Demo local/QA quando não há access token (nunca usa produção).
     */
    protected function demoChargeResult(PaymentMethodType $method, string $external, float $amount): GatewayCheckoutResult
    {
        $id = 'demo_'.Str::lower(Str::random(10));

        if ($method === PaymentMethodType::Pix) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" viewBox="0 0 240 240">'
                .'<rect width="240" height="240" fill="#ffffff"/>'
                .'<rect x="16" y="16" width="64" height="64" fill="#111111"/>'
                .'<rect x="160" y="16" width="64" height="64" fill="#111111"/>'
                .'<rect x="16" y="160" width="64" height="64" fill="#111111"/>'
                .'<rect x="40" y="40" width="16" height="16" fill="#ffffff"/>'
                .'<rect x="184" y="40" width="16" height="16" fill="#ffffff"/>'
                .'<rect x="40" y="184" width="16" height="16" fill="#ffffff"/>'
                .'<rect x="100" y="100" width="40" height="40" fill="#111111"/>'
                .'<text x="120" y="228" text-anchor="middle" font-size="12" fill="#666666">DEMO PIX</text>'
                .'</svg>';

            return new GatewayCheckoutResult(
                gatewaySessionId: $id,
                checkoutUrl: url('/company/financeiro'),
                raw: [
                    'status' => 'pending',
                    'flow' => 'demo_pix',
                    'pix_qr_code' => '00020126580014br.gov.bcb.pix0136DEMOEXPANDORPIX'.substr($external, -8),
                    'pix_qr_code_base64' => base64_encode($svg),
                ],
            );
        }

        return new GatewayCheckoutResult(
            gatewaySessionId: $id,
            checkoutUrl: 'https://example.com/boleto-demo',
            raw: [
                'status' => 'pending',
                'flow' => 'demo_boleto',
                'boleto_url' => 'https://example.com/boleto-demo',
                'digitable_line' => '23790.00000 00000.000000 00000.000000 1 00000000000000',
            ],
        );
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
