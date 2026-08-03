<?php

namespace App\Domains\Sales\SaleFields;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;
use App\Domains\Company\Models\User;
use App\Domains\Sales\Products\Models\Product;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaleFieldsPolicyResolver
{
    public function resolve(?int $companyId): SaleRequiredFieldsPolicy
    {
        if ($companyId === null) {
            return new SaleRequiredFieldsPolicy(SaleFieldKeys::defaults());
        }

        $raw = CompanySetting::query()
            ->where('company_id', $companyId)
            ->where('key', SaleFieldKeys::SETTINGS_KEY)
            ->value('value');

        if ($raw === null || $raw === '') {
            return new SaleRequiredFieldsPolicy(SaleFieldKeys::defaults());
        }

        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return new SaleRequiredFieldsPolicy(SaleFieldKeys::defaults());
        }

        $allowed = array_flip(SaleFieldKeys::all());
        $required = [];
        foreach ($decoded as $key) {
            // Ignore legacy keys (ex.: negotiated_amount) from saved settings.
            if (is_string($key) && isset($allowed[$key])) {
                $required[] = $key;
            }
        }

        return new SaleRequiredFieldsPolicy(array_values(array_unique($required)));
    }

    public function resolveForUser(?User $user): SaleRequiredFieldsPolicy
    {
        return $this->resolve($user?->company_id ? (int) $user->company_id : null);
    }

    /**
     * @param  list<string>  $required
     */
    public function saveForCompany(Company $company, array $required): SaleRequiredFieldsPolicy
    {
        $allowed = array_flip(SaleFieldKeys::all());
        $clean = [];
        foreach ($required as $key) {
            if (is_string($key) && isset($allowed[$key])) {
                $clean[] = $key;
            }
        }
        $clean = array_values(array_unique($clean));

        DB::transaction(function () use ($company, $clean): void {
            CompanySetting::query()->updateOrCreate(
                ['company_id' => $company->id, 'key' => SaleFieldKeys::SETTINGS_KEY],
                ['value' => json_encode($clean, JSON_UNESCAPED_UNICODE)]
            );
        });

        return new SaleRequiredFieldsPolicy($clean);
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRulesForRequest(bool $isSale, ?int $companyId = null): array
    {
        if (! $isSale) {
            return [];
        }

        $companyId ??= app(TenantContext::class)->id();
        $policy = $this->resolve($companyId ? (int) $companyId : null);

        $req = fn (string $field): array => $policy->requires($field)
            ? ['required']
            : ['nullable'];

        return [
            'customer_name' => array_merge($req(SaleFieldKeys::NAME), ['string', 'max:255']),
            'customer_phone' => array_merge($req(SaleFieldKeys::PHONE), ['string', 'max:30']),
            'customer_whatsapp' => array_merge($req(SaleFieldKeys::WHATSAPP), ['string', 'max:30']),
            'customer_document' => array_merge($req(SaleFieldKeys::DOCUMENT), ['string', 'max:32']),
            'customer_rg' => array_merge($req(SaleFieldKeys::RG), ['string', 'max:32']),
            'customer_email' => array_merge($req(SaleFieldKeys::EMAIL), ['email', 'max:255']),
            'sale_notes' => array_merge($req(SaleFieldKeys::NOTES), ['string', 'max:5000']),
            // Carrinho items[] (PDV) ou legado product_id (mobile/compat).
            'items' => array_values(array_filter([
                $policy->requires(SaleFieldKeys::PRODUCT)
                    ? Rule::requiredIf(fn () => ! request()->filled('product_id'))
                    : null,
                'nullable',
                'array',
            ])),
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($q) => $q
                    ->where('company_id', $companyId)
                    ->where('status', Product::STATUS_ACTIVE)),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:9999'],
            'product_id' => array_values(array_filter([
                $policy->requires(SaleFieldKeys::PRODUCT)
                    ? Rule::requiredIf(fn () => empty(request()->input('items')))
                    : null,
                'nullable',
                'integer',
                Rule::exists('products', 'id')->where(fn ($q) => $q
                    ->where('company_id', $companyId)
                    ->where('status', Product::STATUS_ACTIVE)),
            ])),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function validationMessages(): array
    {
        $labels = SaleFieldKeys::labels();

        return [
            'customer_name.required' => 'Informe o '.$labels[SaleFieldKeys::NAME].'.',
            'customer_phone.required' => 'Informe o '.$labels[SaleFieldKeys::PHONE].'.',
            'customer_whatsapp.required' => 'Informe o '.$labels[SaleFieldKeys::WHATSAPP].'.',
            'customer_document.required' => 'Informe o '.$labels[SaleFieldKeys::DOCUMENT].'.',
            'customer_rg.required' => 'Informe o '.$labels[SaleFieldKeys::RG].'.',
            'customer_email.required' => 'Informe o '.$labels[SaleFieldKeys::EMAIL].'.',
            'items.required' => 'Adicione ao menos um produto à venda.',
            'items.min' => 'Adicione ao menos um produto à venda.',
            'items.*.product_id.required' => 'Selecione o produto.',
            'items.*.product_id.exists' => 'Produto inválido ou inativo.',
            'items.*.quantity.required' => 'Informe a quantidade.',
            'items.*.quantity.min' => 'Quantidade mínima: 1.',
            'sale_notes.required' => 'Informe as '.$labels[SaleFieldKeys::NOTES].'.',
            'product_id.exists' => 'Produto inválido ou inativo.',
        ];
    }
}
