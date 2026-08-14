<?php

namespace App\Domains\Sales\Handoff;

final readonly class SaleHandoffData
{
    /**
     * @param  list<array{name: string, quantity: int, amount_label: string}>  $items
     */
    public function __construct(
        public int $saleId,
        public string $companyName,
        public string $customerName,
        public ?string $customerDocument,
        public ?string $customerBirthDate,
        public ?string $customerPhone,
        public ?string $street,
        public ?string $number,
        public ?string $neighborhood,
        public ?string $reference,
        public ?string $city,
        public array $items,
        public string $totalLabel,
        public ?int $dueDay,
        public string $sellerName,
        public ?string $commissionLabel,
        public ?string $commissionStatus,
        public ?float $propertyLatitude,
        public ?float $propertyLongitude,
        public string $message,
        public bool $whatsappEnabled,
        public ?string $whatsappUrl,
        public bool $whatsappConfigured = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $canSend = $this->whatsappEnabled && filled($this->whatsappUrl);

        return [
            'sale_id' => $this->saleId,
            'sale_label' => 'Venda Expandor #'.$this->saleId,
            'company_name' => $this->companyName,
            'customer_name' => $this->customerName,
            'seller_name' => $this->sellerName,
            'commission_label' => $this->commissionLabel,
            'commission_status' => $this->commissionStatus,
            'due_day' => $this->dueDay,
            'items' => $this->items,
            'total_label' => $this->totalLabel,
            'message' => $this->message,
            'whatsapp_enabled' => $this->whatsappEnabled,
            'whatsapp_url' => $this->whatsappUrl,
            'whatsapp_configured' => $this->whatsappConfigured,
            'can_send' => $canSend,
            'copy_available' => $this->message !== '',
            'view_available' => $this->message !== '',
            'maps_url' => $this->mapsUrl(),
        ];
    }

    public function mapsUrl(): ?string
    {
        if ($this->propertyLatitude === null || $this->propertyLongitude === null) {
            return null;
        }

        return sprintf(
            'https://maps.google.com/?q=%s,%s',
            $this->propertyLatitude,
            $this->propertyLongitude
        );
    }
}
