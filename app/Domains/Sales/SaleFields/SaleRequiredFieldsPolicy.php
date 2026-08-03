<?php

namespace App\Domains\Sales\SaleFields;

/**
 * Política de campos obrigatórios na finalização de venda (por empresa).
 */
final class SaleRequiredFieldsPolicy
{
    /**
     * @param  list<string>  $required
     */
    public function __construct(
        public readonly array $required,
    ) {}

    public function requires(string $field): bool
    {
        return in_array($field, $this->required, true);
    }

    /**
     * @return list<string>
     */
    public function required(): array
    {
        return $this->required;
    }

    /**
     * @return array<string, bool>
     */
    public function checklist(): array
    {
        $out = [];
        foreach (SaleFieldKeys::all() as $key) {
            $out[$key] = $this->requires($key);
        }

        return $out;
    }
}
