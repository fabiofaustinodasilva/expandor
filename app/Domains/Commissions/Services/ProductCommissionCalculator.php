<?php

namespace App\Domains\Commissions\Services;

use App\Domains\Commissions\Enums\ProductCommissionType;
use App\Domains\Sales\Products\Models\Product;

/**
 * Calcula comissão de produto field-sales no momento da venda.
 *
 * Arredondamento: PHP_ROUND_HALF_UP em 2 casas para valores monetários.
 * Base percentual: unit_price × quantity (line_total da venda), nunca o preço live do catálogo.
 */
class ProductCommissionCalculator
{
    /**
     * @return array{
     *     commission_type: string,
     *     commission_rate: float,
     *     commission_base: float|null,
     *     commission_amount: float,
     *     unit_price: float,
     *     line_total: float,
     *     quantity: int
     * }
     */
    public function forProduct(Product $product, float $unitPrice, int $quantity): array
    {
        $type = $this->resolveType($product);
        $rate = $type === ProductCommissionType::Percentage
            ? (float) ($product->commission_percentage ?? 0)
            : (float) ($product->commission_amount ?? 0);

        return $this->calculate($type, $rate, $unitPrice, $quantity);
    }

    /**
     * Calcula snapshot de comissão para uma linha de venda.
     *
     * Fixed: amount = round(value × qty, 2, PHP_ROUND_HALF_UP).
     * Percentage: base = round(unitPrice × qty, 2, PHP_ROUND_HALF_UP);
     *            amount = round(base × (rate / 100), 2, PHP_ROUND_HALF_UP).
     * Rate 0 → commission_amount 0 (venda permanece válida).
     *
     * @return array{
     *     commission_type: string,
     *     commission_rate: float,
     *     commission_base: float|null,
     *     commission_amount: float,
     *     unit_price: float,
     *     line_total: float,
     *     quantity: int
     * }
     */
    public function calculate(
        ProductCommissionType|string $type,
        float $rate,
        float $unitPrice,
        int $quantity,
    ): array {
        $resolved = $type instanceof ProductCommissionType
            ? $type
            : ProductCommissionType::tryFrom((string) $type) ?? ProductCommissionType::Fixed;

        $qty = max(1, $quantity);
        $unit = round(max(0, $unitPrice), 2, PHP_ROUND_HALF_UP);
        $lineTotal = round($unit * $qty, 2, PHP_ROUND_HALF_UP);
        $safeRate = max(0, $rate);

        if ($resolved === ProductCommissionType::Percentage) {
            $safeRate = min(100, $safeRate);
            $amount = round($lineTotal * ($safeRate / 100), 2, PHP_ROUND_HALF_UP);

            return [
                'commission_type' => $resolved->value,
                'commission_rate' => round($safeRate, 4, PHP_ROUND_HALF_UP),
                'commission_base' => $lineTotal,
                'commission_amount' => $amount,
                'unit_price' => $unit,
                'line_total' => $lineTotal,
                'quantity' => $qty,
            ];
        }

        $amount = round($safeRate * $qty, 2, PHP_ROUND_HALF_UP);

        return [
            'commission_type' => ProductCommissionType::Fixed->value,
            'commission_rate' => round($safeRate, 4, PHP_ROUND_HALF_UP),
            'commission_base' => null,
            'commission_amount' => $amount,
            'unit_price' => $unit,
            'line_total' => $lineTotal,
            'quantity' => $qty,
        ];
    }

    protected function resolveType(Product $product): ProductCommissionType
    {
        $raw = $product->commission_type ?? null;
        if ($raw instanceof ProductCommissionType) {
            return $raw;
        }

        return ProductCommissionType::tryFrom((string) $raw) ?? ProductCommissionType::Fixed;
    }
}
