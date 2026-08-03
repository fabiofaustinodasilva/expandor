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

            $unitCommission = (float) $product->commission_amount;

            $commission = SalesCommission::query()->create([
                'company_id' => $visit->company_id,
                'user_id' => $visit->user_id,
                'visit_id' => $visit->id,
                'sale_item_id' => $saleItem?->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'commission_amount' => round($unitCommission * $quantity, 2),
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
