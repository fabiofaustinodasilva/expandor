<?php

namespace App\Domains\Visits\Services;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Commissions\Actions\GenerateVisitCommissionAction;
use App\Domains\Commissions\Services\ProductCommissionCalculator;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Models\Sale;
use App\Domains\Sales\Models\SaleItem;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Services\StockService;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Sales\Properties\Services\PropertyService;
use App\Domains\Sales\Residents\Services\ResidentService;
use App\Domains\Sales\Support\SaleDueDays;
use App\Domains\Security\Services\SecurityService;
use App\Domains\Visits\Enums\FollowUpStatus;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\FollowUp;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Support\FollowUpSchedule;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VisitService
{
    public function __construct(
        protected PropertyService $properties,
        protected SecurityService $security,
        protected StockService $stock,
        protected GenerateVisitCommissionAction $generateCommission,
        protected ResidentService $residents,
        protected ProductCommissionCalculator $commissionCalculator,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(Campaign $campaign, array $data, ?User $actor = null): Visit
    {
        return DB::transaction(function () use ($campaign, $data, $actor) {
            /** @var TenantContext $context */
            $context = app(TenantContext::class);
            $user = $actor ?? $context->user();

            if ($user === null) {
                throw ValidationException::withMessages([
                    'user_id' => 'Usuário responsável pela visita não identificado.',
                ]);
            }

            /** @var Property $property */
            $property = Property::query()->findOrFail($data['property_id']);

            if ((int) $property->company_id !== (int) $campaign->company_id) {
                throw ValidationException::withMessages([
                    'property_id' => 'O imóvel não pertence à empresa da campanha.',
                ]);
            }

            $status = VisitStatus::from($data['status']);
            $visitedAt = $data['visited_at'] ?? now();
            $latitude = $data['latitude'] ?? $property->latitude;
            $longitude = $data['longitude'] ?? $property->longitude;

            $product = null;
            $plan = isset($data['plan']) ? trim((string) $data['plan']) : null;
            $plan = $plan !== '' ? $plan : null;
            $productId = null;
            $cartLines = [];

            if ($status === VisitStatus::INSTALLATION_REQUESTED) {
                $cartLines = $this->normalizeSaleCartLines($data, (int) $campaign->company_id);
                if ($cartLines !== []) {
                    $names = [];
                    foreach ($cartLines as $line) {
                        $this->stock->assertAvailable($line['product'], $line['quantity']);
                        $names[] = $line['product']->name.($line['quantity'] > 1 ? ' x'.$line['quantity'] : '');
                    }
                    $first = $cartLines[0]['product'];
                    $product = $first;
                    $productId = $first->id;
                    $plan = implode(', ', $names);
                }
            }

            $visit = Visit::query()->create([
                'campaign_id' => $campaign->id,
                'property_id' => $property->id,
                'user_id' => $data['user_id'] ?? $user->id,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'plan' => $plan,
                'product_id' => $productId,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'visited_at' => $visitedAt,
            ]);

            $extras = [];
            if (filled($plan)) {
                $extras[] = 'Produto: '.$plan;
            }
            if (filled($visit->notes)) {
                $extras[] = $visit->notes;
            }

            $isFirst = ! empty($data['first_approach']);
            if ($isFirst) {
                $historyDescription = sprintf(
                    'Primeiro atendimento realizado — Resultado: %s%s',
                    $status->label(),
                    $extras !== [] ? "\nObservação: ".implode(' · ', $extras) : ''
                );
            } else {
                $historyDescription = sprintf(
                    'Visita #%d — %s%s',
                    $visit->id,
                    $status->label(),
                    $extras !== [] ? ': '.implode(' · ', $extras) : '.'
                );
            }

            $this->properties->applyVisitOutcome(
                property: $property,
                user: $user,
                newStatus: $status->toPropertyStatus(),
                description: $historyDescription,
                latitude: $latitude,
                longitude: $longitude,
            );

            if ($status === VisitStatus::INSTALLATION_REQUESTED) {
                \Illuminate\Support\Facades\Log::info('[SalePropertyTrace] before-sale', [
                    'property_id' => $property->id,
                    'lat' => $property->latitude,
                    'lng' => $property->longitude,
                    'status' => $property->status?->value ?? (string) $property->getAttribute('status'),
                ]);

                $this->applyInstallationAddress($property, $data);

                $phone = $data['customer_phone'] ?? $data['contact_phone'] ?? null;
                $whatsapp = $data['customer_whatsapp'] ?? null;
                if (filled($phone) && ! filled($whatsapp)) {
                    $whatsapp = $phone;
                }

                $resident = $this->residents->upsertPrimaryContact($property, [
                    'name' => $data['customer_name'] ?? $data['contact_name'] ?? null,
                    'phone' => $phone,
                    'whatsapp' => $whatsapp,
                    'email' => $data['customer_email'] ?? null,
                    'document' => $data['customer_document'] ?? null,
                    'birth_date' => $data['customer_birth_date'] ?? null,
                ]);

                $attributes = [];
                $rg = isset($data['customer_rg']) ? trim((string) $data['customer_rg']) : '';
                if ($rg !== '') {
                    $attributes['rg'] = $rg;
                }

                $dueDay = $this->normalizeDueDay($data['due_day'] ?? null);

                $total = 0.0;
                foreach ($cartLines as $line) {
                    $total += $line['line_total'];
                }

                $sale = Sale::query()->create([
                    'company_id' => $campaign->company_id,
                    'visit_id' => $visit->id,
                    'resident_id' => $resident?->id,
                    'product_id' => $productId,
                    'negotiated_amount' => round($total, 2),
                    'due_day' => $dueDay,
                    'notes' => $data['sale_notes'] ?? null,
                    'attributes' => $attributes !== [] ? $attributes : null,
                ]);

                foreach ($cartLines as $line) {
                    /** @var Product $lineProduct */
                    $lineProduct = $line['product'];
                    $item = SaleItem::query()->create([
                        'company_id' => $campaign->company_id,
                        'sale_id' => $sale->id,
                        'product_id' => $lineProduct->id,
                        'product_name' => $lineProduct->name,
                        'unit_price' => $line['unit_price'],
                        'quantity' => $line['quantity'],
                        'line_total' => $line['line_total'],
                        'commission_amount' => $line['commission_amount'],
                        'commission_type' => $line['commission_type'],
                        'commission_rate' => $line['commission_rate'],
                        'commission_base' => $line['commission_base'],
                    ]);

                    $this->generateCommission->executeForSaleItem($visit, $item, $lineProduct, $user);
                }

                $property->refresh();
                \Illuminate\Support\Facades\Log::info('[SalePropertyTrace] after-sale', [
                    'property_id' => $property->id,
                    'lat' => $property->latitude,
                    'lng' => $property->longitude,
                    'status' => $property->status?->value ?? (string) $property->getAttribute('status'),
                ]);
            }

            $this->security->recordAudit(
                action: 'visit.registered',
                user: $user,
                auditable: $visit,
                newValues: [
                    'property_id' => $property->id,
                    'campaign_id' => $campaign->id,
                    'status' => $status->value,
                    'plan' => $plan,
                    'product_id' => $productId,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'visited_at' => (string) $visit->visited_at,
                    'first_approach' => $isFirst,
                ],
            );

            return $visit->load(['property.address', 'user', 'campaign', 'product', 'sale.items']);
        });
    }

    /**
     * Normaliza carrinho: items[] ou legado product_id único.
     *
     * @param  array<string, mixed>  $data
     * @return list<array{
     *     product: Product,
     *     quantity: int,
     *     unit_price: float,
     *     line_total: float,
     *     commission_amount: float,
     *     commission_type: string,
     *     commission_rate: float,
     *     commission_base: float|null
     * }>
     */
    protected function normalizeSaleCartLines(array $data, int $companyId): array
    {
        $rawItems = $data['items'] ?? null;
        if (! is_array($rawItems) || $rawItems === []) {
            $legacyId = isset($data['product_id']) ? (int) $data['product_id'] : 0;
            if ($legacyId <= 0) {
                return [];
            }
            $rawItems = [['product_id' => $legacyId, 'quantity' => 1]];
        }

        $merged = [];
        foreach ($rawItems as $row) {
            if (! is_array($row)) {
                continue;
            }
            $pid = (int) ($row['product_id'] ?? 0);
            $qty = max(1, (int) ($row['quantity'] ?? 1));
            if ($pid <= 0) {
                continue;
            }
            $merged[$pid] = ($merged[$pid] ?? 0) + $qty;
        }

        $lines = [];
        foreach ($merged as $pid => $qty) {
            $product = $this->resolveContractProduct($pid, $companyId);
            $unitPrice = (float) $product->price;
            $calc = $this->commissionCalculator->forProduct($product, $unitPrice, $qty);
            $lines[] = [
                'product' => $product,
                'quantity' => $calc['quantity'],
                'unit_price' => $calc['unit_price'],
                'line_total' => $calc['line_total'],
                'commission_amount' => $calc['commission_amount'],
                'commission_type' => $calc['commission_type'],
                'commission_rate' => $calc['commission_rate'],
                'commission_base' => $calc['commission_base'],
            ];
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function scheduleFollowUp(Visit $visit, array $data, ?User $actor = null): FollowUp
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);
        $user = $actor ?? $context->user();

        if ($user === null) {
            throw ValidationException::withMessages([
                'user_id' => 'Usuário responsável pelo retorno não identificado.',
            ]);
        }

        return FollowUp::query()->create([
            'visit_id' => $visit->id,
            'user_id' => $data['user_id'] ?? $user->id,
            'scheduled_at' => FollowUpSchedule::normalize($data['scheduled_at']),
            'status' => FollowUpStatus::PENDING,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function completeFollowUp(FollowUp $followUp, ?string $notes = null): FollowUp
    {
        if ($followUp->status !== FollowUpStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => 'Somente retornos pendentes podem ser concluídos.',
            ]);
        }

        $followUp->update([
            'status' => FollowUpStatus::COMPLETED,
            'completed_at' => now(),
            'notes' => $notes ?? $followUp->notes,
        ]);

        return $followUp->refresh();
    }

    /**
     * Conclui retorno na Agenda: cria Visit real, marca FollowUp como concluído
     * e, se o resultado for "Retornar", agenda um novo FollowUp.
     *
     * @param  array<string, mixed>  $data
     * @return array{visit: Visit, follow_up: FollowUp, next_follow_up: ?FollowUp}
     */
    public function completeFollowUpWithOutcome(FollowUp $followUp, array $data, User $actor): array
    {
        return DB::transaction(function () use ($followUp, $data, $actor) {
            if ($followUp->status !== FollowUpStatus::PENDING) {
                throw ValidationException::withMessages([
                    'status' => 'Somente retornos pendentes podem ser concluídos.',
                ]);
            }

            $originVisit = $followUp->visit()->with(['campaign', 'property'])->firstOrFail();
            $campaign = $originVisit->campaign;
            $property = $originVisit->property;

            if ($campaign === null || $property === null) {
                throw ValidationException::withMessages([
                    'visit_id' => 'Retorno sem visita ou imóvel vinculado.',
                ]);
            }

            $status = VisitStatus::from($data['status']);

            $visit = $this->register($campaign, [
                'property_id' => $property->id,
                'status' => $status->value,
                'notes' => $data['notes'] ?? null,
                'plan' => $data['plan'] ?? null,
                'product_id' => $data['product_id'] ?? null,
                'items' => $data['items'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_whatsapp' => $data['customer_whatsapp'] ?? null,
                'customer_document' => $data['customer_document'] ?? null,
                'customer_rg' => $data['customer_rg'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_birth_date' => $data['customer_birth_date'] ?? null,
                'due_day' => $data['due_day'] ?? null,
                'install_street' => $data['install_street'] ?? null,
                'install_number' => $data['install_number'] ?? null,
                'install_neighborhood' => $data['install_neighborhood'] ?? null,
                'install_reference' => $data['install_reference'] ?? null,
                'install_city' => $data['install_city'] ?? null,
                'sale_notes' => $data['sale_notes'] ?? null,
                'user_id' => $actor->id,
                'latitude' => $data['latitude'] ?? $property->latitude ?? $originVisit->latitude,
                'longitude' => $data['longitude'] ?? $property->longitude ?? $originVisit->longitude,
                'visited_at' => $data['visited_at'] ?? now(),
            ], $actor);

            $completed = $this->completeFollowUp($followUp, $data['notes'] ?? $followUp->notes);

            $nextFollowUp = null;
            if ($status === VisitStatus::RETURN_LATER) {
                if (empty($data['follow_up_at'])) {
                    throw ValidationException::withMessages([
                        'follow_up_at' => 'Informe a data do novo retorno.',
                    ]);
                }

                $nextFollowUp = $this->scheduleFollowUp($visit, [
                    'scheduled_at' => $data['follow_up_at'],
                    'notes' => $data['follow_up_notes'] ?? null,
                    'user_id' => $followUp->user_id,
                ], $actor);
            }

            $this->security->recordAudit(
                action: 'follow_up.completed_with_outcome',
                user: $actor,
                auditable: $completed,
                newValues: [
                    'follow_up_id' => $completed->id,
                    'visit_id' => $visit->id,
                    'status' => $status->value,
                    'next_follow_up_id' => $nextFollowUp?->id,
                ],
            );

            return [
                'visit' => $visit,
                'follow_up' => $completed,
                'next_follow_up' => $nextFollowUp,
            ];
        });
    }

    protected function resolveContractProduct(?int $productId, int $companyId): Product
    {
        if ($productId === null || $productId <= 0) {
            throw ValidationException::withMessages([
                'product_id' => 'Selecione o produto contratado.',
            ]);
        }

        /** @var Product|null $product */
        $product = Product::query()
            ->whereKey($productId)
            ->where('company_id', $companyId)
            ->first();

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => 'Produto não encontrado.',
            ]);
        }

        if (! $product->isActive()) {
            throw ValidationException::withMessages([
                'product_id' => 'Produto inativo não pode ser vendido.',
            ]);
        }

        return $product;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function applyInstallationAddress(Property $property, array $data): void
    {
        $address = $property->address;
        if ($address === null) {
            return;
        }

        $street = isset($data['install_street']) ? trim((string) $data['install_street']) : '';
        if ($street === '' && isset($data['street'])) {
            $street = trim((string) $data['street']);
        }
        $number = isset($data['install_number']) ? trim((string) $data['install_number']) : '';
        if ($number === '' && isset($data['number'])) {
            $number = trim((string) $data['number']);
        }
        $neighborhood = isset($data['install_neighborhood']) ? trim((string) $data['install_neighborhood']) : '';
        if ($neighborhood === '' && isset($data['neighborhood'])) {
            $neighborhood = trim((string) $data['neighborhood']);
        }
        $reference = isset($data['install_reference']) ? trim((string) $data['install_reference']) : '';

        $updates = [];
        if ($street !== '') {
            $updates['street'] = $street;
        }
        if ($number !== '') {
            $updates['number'] = $number;
        }
        if ($neighborhood !== '') {
            $updates['neighborhood'] = $neighborhood;
        }
        if ($reference !== '') {
            $updates['reference'] = $reference;
        }
        // install_city is captured for handoff/display only.
        // Never retarget city_id — that would drop the pin from campaign marker queries.

        if ($updates !== []) {
            $address->update($updates);
        }
    }

    protected function normalizeDueDay(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! SaleDueDays::isValid($value)) {
            throw ValidationException::withMessages([
                'due_day' => 'Escolha o vencimento: 5, 10, 15, 20, 25 ou 30.',
            ]);
        }

        return (int) $value;
    }
}
