<?php

namespace App\Http\Controllers\Web\Payments;

use App\Domains\Company\Models\Company;
use App\Domains\Payments\Enums\PaymentMethodType;
use App\Domains\Payments\Models\Invoice;
use App\Domains\Payments\Exceptions\PaymentGatewayClientException;
use App\Domains\Payments\Services\BillingDelinquencyPolicy;
use App\Domains\Payments\Services\BillingFidelityService;
use App\Domains\Payments\Services\InvoicePaymentService;
use App\Domains\Payments\Services\RecurringBillingService;
use App\Domains\Payments\Services\SubscriptionService;
use App\Domains\Payments\Services\UpcomingBillingScheduleService;
use App\Http\Controllers\Controller;
use App\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CompanyFinanceController extends Controller
{
    public function __construct(
        protected TenantContext $tenant,
        protected SubscriptionService $subscriptions,
        protected BillingFidelityService $fidelity,
        protected BillingDelinquencyPolicy $delinquency,
        protected InvoicePaymentService $invoicePayments,
        protected UpcomingBillingScheduleService $upcomingBilling,
        protected RecurringBillingService $recurringBilling,
    ) {}

    public function index(Request $request): View
    {
        $company = $this->resolveCompany($request);
        $this->authorize('billing.view', $company);

        return view('payments.finance.index', $this->payload($company));
    }

    public function pending(Request $request): View
    {
        $company = $this->resolveCompany($request);
        $this->authorize('billing.view', $company);

        return view('payments.finance.pending', $this->payload($company));
    }

    public function showInvoice(Request $request, Invoice $invoice): View
    {
        $company = $this->resolveCompany($request);
        $this->authorize('billing.view', $company);
        abort_unless((int) $invoice->company_id === (int) $company->id, 404);

        $data = $this->payload($company);
        $data['invoice'] = $invoice->loadMissing(['plan', 'payments']);

        return view('payments.finance.invoice', $data);
    }

    public function payPix(Request $request, Invoice $invoice): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('billing.manage', $company);
        abort_unless((int) $invoice->company_id === (int) $company->id, 404);

        try {
            $charge = $this->invoicePayments->payWithPix($invoice);
        } catch (PaymentGatewayClientException $e) {
            return back()->with('error', $e->userMessage());
        } catch (RuntimeException $e) {
            return back()->with('error', 'Não foi possível gerar o PIX. Verifique os dados de cobrança da empresa ou entre em contato com o suporte.');
        }

        return view('payments.finance.pix', array_merge($this->payload($company), [
            'invoice' => $invoice->fresh(),
            'charge' => $charge,
        ]));
    }

    public function payBoleto(Request $request, Invoice $invoice): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('billing.manage', $company);
        abort_unless((int) $invoice->company_id === (int) $company->id, 404);

        try {
            $charge = $this->invoicePayments->payWithBoleto($invoice);
        } catch (PaymentGatewayClientException $e) {
            return back()->with('error', $e->userMessage());
        } catch (RuntimeException $e) {
            return back()->with('error', 'Não foi possível gerar o boleto. Verifique os dados de cobrança da empresa ou entre em contato com o suporte.');
        }

        return view('payments.finance.boleto', array_merge($this->payload($company), [
            'invoice' => $invoice->fresh(),
            'charge' => $charge,
        ]));
    }

    public function secondCopy(Request $request, Invoice $invoice): View|RedirectResponse
    {
        $company = $this->resolveCompany($request);
        $this->authorize('billing.manage', $company);
        abort_unless((int) $invoice->company_id === (int) $company->id, 404);

        $method = $request->string('method')->toString() === 'boleto'
            ? PaymentMethodType::Boleto
            : PaymentMethodType::Pix;

        $existing = $this->invoicePayments->existingPendingCharge($invoice, $method);

        if ($existing === null) {
            return $method === PaymentMethodType::Boleto
                ? $this->payBoleto($request, $invoice)
                : $this->payPix($request, $invoice);
        }

        $view = $method === PaymentMethodType::Boleto ? 'payments.finance.boleto' : 'payments.finance.pix';

        return view($view, array_merge($this->payload($company), [
            'invoice' => $invoice->fresh(),
            'charge' => $existing,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(Company $company): array
    {
        $overview = $this->subscriptions->overview($company);
        $subscription = $overview->subscription;
        $currentInvoice = $overview->invoices
            ->first(fn (Invoice $invoice) => $invoice->status->isPayable());

        $upcomingCharges = $subscription
            ? $this->upcomingBilling->upcomingCharges($subscription)
            : collect();

        $nextChargePreview = ($subscription && $currentInvoice === null)
            ? $this->upcomingBilling->nextChargePreview($subscription)
            : null;

        return [
            'company' => $company,
            'overview' => $overview,
            'subscription' => $subscription,
            'plan' => $subscription?->plan,
            'fidelity' => $subscription ? $this->fidelity->progress($subscription) : null,
            'currentInvoice' => $currentInvoice,
            'upcomingCharges' => $upcomingCharges,
            'nextChargePreview' => $nextChargePreview,
            'invoiceGenerationDays' => $this->recurringBilling->invoiceGenerationDays(),
            'daysPastDue' => $currentInvoice?->due_at
                ? $this->delinquency->daysPastDue($currentInvoice->due_at)
                : 0,
            'withinGrace' => $currentInvoice ? $this->delinquency->isWithinGrace($currentInvoice) : false,
            'graceDays' => $this->delinquency->graceDays(),
        ];
    }

    protected function resolveCompany(Request $request): Company
    {
        $company = $this->tenant->company() ?? $request->user()?->company;
        abort_if($company === null, 404);

        return $company;
    }
}
