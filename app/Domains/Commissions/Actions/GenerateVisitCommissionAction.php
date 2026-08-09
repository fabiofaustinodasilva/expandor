<?php

namespace App\Domains\Commissions\Actions;

use App\Domains\Commissions\Enums\SalesCommissionStatus;
use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Models\SaleItem;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Services\StockService;
use App\Domains\Security\Services\SecurityService;
use App\Domains\Visits\Models\Visit;
use Illuminate\Support\Facades\DB;

class GenerateVisitCommissionAction
{
    public function __construct(
        protected StockService $stock,
        protected SecurityService $security,
    ) {}

    /**
     * Após visita Contratou: gera comissão (idempotente) e baixa estoque se necessário.
     * Preferir executeForSaleItem() no fluxo multi-produto.
     *
     * Fonte do valor: snapshot do SaleItem quando disponível (nunca recalc live do produto
     * se o item já materializou commission_amount).
     */
    public function execute(Visit $visit, Product $product, User $actor, int $quantity = 1, ?SaleItem $saleItem = null): SalesCommission
    {
        return DB::transaction(function () use ($visit, $product, $actor, $quantity, $saleItem) {
            // Idempotência: por item da venda (PDV) ou, no legado, por visita+produto.
            $existing = $saleItem !== null
                ? SalesCommission::query()
                    ->where('sale_item_id', $saleItem->id)
                    ->lockForUpdate()
                    ->first()
                : SalesCommission::query()
                    ->where('visit_id', $visit->id)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

            if ($existing !== null) {
                return $existing;
            }

            $this->stock->assertAvailable($product, $quantity);

            $amount = $saleItem !== null
                ? (float) $saleItem->commission_amount
                : round((float) $product->commission_amount * $quantity, 2);

            $type = $saleItem?->commission_type
                ?? (string) ($product->commission_type?->value ?? $product->commission_type ?? 'fixed');
            $rate = $saleItem !== null
                ? ($saleItem->commission_rate !== null ? (float) $saleItem->commission_rate : null)
                : (float) $product->commission_amount;
            $base = $saleItem?->commission_base !== null ? (float) $saleItem->commission_base : null;

            $commission = SalesCommission::query()->create([
                'company_id' => $visit->company_id,
                'user_id' => $visit->user_id,
                'visit_id' => $visit->id,
                'sale_item_id' => $saleItem?->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'commission_amount' => $amount,
                'commission_type' => $type,
                'commission_rate' => $rate,
                'commission_base' => $base,
                'quantity' => $quantity,
                'status' => SalesCommissionStatus::PENDING,
                'earned_at' => $visit->visited_at ?? now(),
            ]);

            $this->stock->sale(
                product: $product,
                quantity: $quantity,
                actor: $actor,
                reference: $visit,
                notes: 'Venda visita #'.$visit->id.($saleItem ? ' item #'.$saleItem->id : ''),
            );

            $this->security->recordAudit(
                action: 'sales_commission.created',
                user: $actor,
                auditable: $commission,
                newValues: [
                    'visit_id' => $visit->id,
                    'sale_item_id' => $saleItem?->id,
                    'product_id' => $product->id,
                    'commission_amount' => $commission->commission_amount,
                    'commission_type' => $commission->commission_type,
                    'commission_rate' => $commission->commission_rate,
                    'commission_base' => $commission->commission_base,
                    'quantity' => $quantity,
                ],
            );

            return $commission;
        });
    }

    public function executeForSaleItem(Visit $visit, SaleItem $item, Product $product, User $actor): SalesCommission
    {
        return $this->execute($visit, $product, $actor, (int) $item->quantity, $item);
    }
}
