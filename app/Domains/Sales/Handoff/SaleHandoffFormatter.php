<?php

namespace App\Domains\Sales\Handoff;

use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Sales\Models\Sale;

class SaleHandoffFormatter
{
    public function __construct(
        protected OfficeSalesWhatsAppSettings $officeWhatsApp,
    ) {}

    public function format(Sale $sale): SaleHandoffData
    {
        $sale->loadMissing([
            'company',
            'items',
            'resident',
            'visit.user',
            'visit.property.address.city',
        ]);

        $company = $sale->company;
        $resident = $sale->resident;
        $visit = $sale->visit;
        $property = $visit?->property;
        $address = $property?->address;
        $sellerName = (string) ($visit?->user?->name ?: '—');

        $items = [];
        foreach ($sale->items as $item) {
            $qty = max(1, (int) $item->quantity);
            $items[] = [
                'name' => (string) $item->product_name,
                'quantity' => $qty,
                'amount_label' => $this->money((float) $item->line_total),
            ];
        }

        $commissions = SalesCommission::query()
            ->where('visit_id', $sale->visit_id)
            ->get();
        $commissionTotal = round((float) $commissions->sum('commission_amount'), 2);
        $commissionLabel = $commissions->isEmpty() ? null : $this->money($commissionTotal);
        $commissionStatus = $this->commissionStatusLabel($commissions);

        $office = $company ? $this->officeWhatsApp->forCompany($company) : [
            'enabled_flag' => false,
            'enabled' => false,
            'number' => '',
            'digits' => '',
        ];

        $lat = $property?->latitude !== null ? (float) $property->latitude : null;
        $lng = $property?->longitude !== null ? (float) $property->longitude : null;

        $data = new SaleHandoffData(
            saleId: (int) $sale->id,
            companyName: (string) ($company?->name ?: 'Expandor'),
            customerName: (string) ($resident?->name ?: '—'),
            customerDocument: $this->filled($resident?->document),
            customerBirthDate: $resident?->birth_date?->format('d/m/Y'),
            customerPhone: $this->filled($resident?->whatsapp ?: $resident?->phone),
            street: $this->filled($address?->street),
            number: $this->filled($address?->number),
            neighborhood: $this->filled($address?->neighborhood),
            reference: $this->filled($address?->reference),
            city: $this->filled($address?->city?->name),
            items: $items,
            totalLabel: $this->money((float) $sale->negotiated_amount),
            dueDay: $sale->due_day !== null ? (int) $sale->due_day : null,
            sellerName: $sellerName,
            commissionLabel: $commissionLabel,
            commissionStatus: $commissionStatus,
            propertyLatitude: $lat,
            propertyLongitude: $lng,
            message: '',
            whatsappEnabled: (bool) ($office['enabled'] ?? false),
            whatsappUrl: null,
            whatsappConfigured: ($office['digits'] ?? '') !== '',
        );

        $message = $this->composeMessage($data);
        $whatsappUrl = null;
        if ($data->whatsappEnabled && ($office['digits'] ?? '') !== '') {
            $whatsappUrl = 'https://wa.me/'.$office['digits'].'?text='.rawurlencode($message);
        }

        return new SaleHandoffData(
            saleId: $data->saleId,
            companyName: $data->companyName,
            customerName: $data->customerName,
            customerDocument: $data->customerDocument,
            customerBirthDate: $data->customerBirthDate,
            customerPhone: $data->customerPhone,
            street: $data->street,
            number: $data->number,
            neighborhood: $data->neighborhood,
            reference: $data->reference,
            city: $data->city,
            items: $data->items,
            totalLabel: $data->totalLabel,
            dueDay: $data->dueDay,
            sellerName: $data->sellerName,
            commissionLabel: $data->commissionLabel,
            commissionStatus: $data->commissionStatus,
            propertyLatitude: $data->propertyLatitude,
            propertyLongitude: $data->propertyLongitude,
            message: $message,
            whatsappEnabled: $data->whatsappEnabled,
            whatsappUrl: $whatsappUrl,
            whatsappConfigured: $data->whatsappConfigured,
        );
    }

    protected function composeMessage(SaleHandoffData $data): string
    {
        $lines = [
            '🟢 NOVA VENDA — '.$data->companyName,
            'Venda Expandor: #'.$data->saleId,
            '',
            'DADOS DO CLIENTE',
            'Nome: '.$data->customerName,
        ];
        $this->push($lines, 'CPF', $data->customerDocument);
        $this->push($lines, 'Nascimento', $data->customerBirthDate);
        $this->push($lines, 'Telefone/WhatsApp', $data->customerPhone);
        $lines[] = '';
        $lines[] = 'ENDEREÇO DA INSTALAÇÃO';
        $this->push($lines, 'Rua/Avenida', $data->street);
        $this->push($lines, 'Número', $data->number);
        $this->push($lines, 'Bairro', $data->neighborhood);
        $this->push($lines, 'Referência', $data->reference);
        $this->push($lines, 'Cidade', $data->city);
        $lines[] = '';
        $lines[] = 'CONTRATAÇÃO';
        if (count($data->items) > 1) {
            foreach ($data->items as $item) {
                $lines[] = $item['quantity'].'x '.$item['name'].' — '.$item['amount_label'];
            }
            $lines[] = 'Total: '.$data->totalLabel;
        } elseif (count($data->items) === 1) {
            $item = $data->items[0];
            $lines[] = 'Plano/Produto: '.$item['name'];
            $lines[] = 'Valor: '.$item['amount_label'];
        } else {
            $lines[] = 'Valor: '.$data->totalLabel;
        }
        if ($data->dueDay !== null) {
            $lines[] = 'Vencimento: Dia '.$data->dueDay;
        }
        $lines[] = '';
        $lines[] = 'VENDEDOR RESPONSÁVEL';
        $lines[] = $data->sellerName;
        if ($data->commissionLabel !== null) {
            $lines[] = '';
            $lines[] = 'COMISSÃO';
            $lines[] = $data->commissionLabel;
            if ($data->commissionStatus !== null) {
                $lines[] = 'Status: '.$data->commissionStatus;
            }
        }
        if ($data->mapsUrl() !== null) {
            $lines[] = '';
            $lines[] = 'LOCALIZAÇÃO DO IMÓVEL';
            $lines[] = $data->mapsUrl();
        }
        $lines[] = '';
        $lines[] = 'Cadastro enviado pelo Expandor.';

        return implode("\n", $lines);
    }

    /**
     * @param  list<string>  $lines
     */
    protected function push(array &$lines, string $label, ?string $value): void
    {
        $value = $this->filled($value);
        if ($value === null) {
            return;
        }
        $lines[] = $label.': '.$value;
    }

    protected function filled(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || strcasecmp($value, 'null') === 0) {
            return null;
        }

        return $value;
    }

    protected function money(float $amount): string
    {
        return 'R$ '.number_format($amount, 2, ',', '.');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SalesCommission>  $commissions
     */
    protected function commissionStatusLabel($commissions): ?string
    {
        if ($commissions->isEmpty()) {
            return null;
        }
        if ($commissions->every(fn (SalesCommission $row) => $row->status === SalesCommissionStatus::PAID)) {
            return 'Pago';
        }
        if ($commissions->every(fn (SalesCommission $row) => $row->status === SalesCommissionStatus::APPROVED)) {
            return 'Aprovada';
        }

        return 'Pendente';
    }
}
