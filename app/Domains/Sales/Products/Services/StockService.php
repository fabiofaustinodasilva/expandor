<?php

namespace App\Domains\Sales\Products\Services;

use App\Domains\Company\Models\User;
use App\Domains\Sales\Products\Enums\StockMovementType;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Models\StockMovement;
use App\Domains\Security\Services\SecurityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function __construct(
        protected SecurityService $security,
    ) {}

    /**
     * Entrada de estoque (somente gestor).
     */
    public function entry(Product $product, int $quantity, User $actor, ?string $notes = null): StockMovement
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Informe uma quantidade positiva.',
            ]);
        }

        return $this->applyDelta(
            product: $product,
            delta: $quantity,
            type: StockMovementType::ENTRY,
            actor: $actor,
            notes: $notes,
            reference: null,
        );
    }

    /**
     * Ajuste manual (+/-). Quantity é o delta (positivo ou negativo).
     */
    public function adjustment(Product $product, int $delta, User $actor, ?string $notes = null): StockMovement
    {
        if ($delta === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Informe um ajuste diferente de zero.',
            ]);
        }

        return $this->applyDelta(
            product: $product,
            delta: $delta,
            type: StockMovementType::ADJUSTMENT,
            actor: $actor,
            notes: $notes,
            reference: null,
        );
    }

    /**
     * Baixa por venda — exige stock_control ativo.
     */
    public function sale(Product $product, int $quantity, User $actor, Model $reference, ?string $notes = null): ?StockMovement
    {
        if (! $product->stock_control) {
            return null;
        }

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'product_id' => 'Quantidade de venda inválida.',
            ]);
        }

        return $this->applyDelta(
            product: $product,
            delta: -$quantity,
            type: StockMovementType::SALE,
            actor: $actor,
            notes: $notes,
            reference: $reference,
        );
    }

    public function assertAvailable(Product $product, int $quantity = 1): void
    {
        if (! $product->isActive()) {
            throw ValidationException::withMessages([
                'product_id' => 'Produto inativo não pode ser vendido.',
            ]);
        }

        if ($product->stock_control && $product->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'product_id' => 'Estoque insuficiente para este produto.',
            ]);
        }
    }

    protected function applyDelta(
        Product $product,
        int $delta,
        StockMovementType $type,
        User $actor,
        ?string $notes,
        ?Model $reference,
    ): StockMovement {
        return DB::transaction(function () use ($product, $delta, $type, $actor, $notes, $reference) {
            /** @var Product $locked */
            $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            $next = $locked->stock_quantity + $delta;
            if ($next < 0) {
                throw ValidationException::withMessages([
                    'product_id' => 'Estoque insuficiente para este produto.',
                ]);
            }

            $locked->update(['stock_quantity' => $next]);

            $movement = StockMovement::query()->create([
                'company_id' => $locked->company_id,
                'product_id' => $locked->id,
                'user_id' => $actor->id,
                'type' => $type,
                'quantity' => $delta,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
                'created_at' => now(),
            ]);

            $this->security->recordAudit(
                action: 'stock.'.$type->value,
                user: $actor,
                auditable: $locked,
                newValues: [
                    'product_id' => $locked->id,
                    'delta' => $delta,
                    'stock_quantity' => $next,
                    'movement_id' => $movement->id,
                    'reference_type' => $movement->reference_type,
                    'reference_id' => $movement->reference_id,
                ],
            );

            return $movement;
        });
    }
}
