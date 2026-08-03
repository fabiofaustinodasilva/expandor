<?php

namespace App\Domains\Platform\Requests;

use App\Domains\Company\Models\Plan;
use App\Domains\Platform\Support\PlanCatalog;
use Illuminate\Foundation\Http\FormRequest;

class StorePlatformPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('platform.managePlans') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $featureRules = [];
        foreach (PlanCatalog::featureKeys() as $key) {
            $featureRules["features.{$key}"] = ['sometimes', 'boolean'];
        }

        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['nullable', 'numeric', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_users' => ['nullable', 'integer', 'min:0'],
            'max_properties' => ['nullable', 'integer', 'min:0'],
            'max_campaigns' => ['nullable', 'integer', 'min:0'],
            'max_teams' => ['nullable', 'integer', 'min:0'],
            'max_products' => ['nullable', 'integer', 'min:0'],
            'max_storage_mb' => ['nullable', 'integer', 'min:0'],
            'max_visits' => ['nullable', 'integer', 'min:0'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_featured' => ['sometimes', 'boolean'],
            'features' => ['nullable', 'array'],
            'status' => ['required', 'in:'.Plan::STATUS_ACTIVE.','.Plan::STATUS_INACTIVE],
        ], $featureRules);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'slug' => 'slug',
            'price' => 'preço mensal',
            'price_yearly' => 'preço anual',
            'trial_days' => 'dias de trial',
            'max_visits' => 'máx. visitas',
            'display_order' => 'ordem de exibição',
            'is_featured' => 'destaque',
            'status' => 'status',
        ];
    }

    protected function prepareForValidation(): void
    {
        $features = $this->input('features', []);
        if (! is_array($features)) {
            $features = [];
        }

        $normalized = [];
        foreach (PlanCatalog::featureKeys() as $key) {
            $normalized[$key] = filter_var($features[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $this->merge([
            'features' => $normalized,
            'is_featured' => filter_var($this->input('is_featured', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
