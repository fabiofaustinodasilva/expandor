<?php

namespace App\Domains\Branding\Repositories;

use App\Domains\Branding\Models\Brand;
use App\Domains\Company\Models\Company;

class BrandRepository
{
    public function findForCompany(Company $company): ?Brand
    {
        return Brand::query()
            ->withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->first();
    }

    public function findByCustomDomain(string $domain): ?Brand
    {
        $normalized = strtolower(trim($domain));

        if ($normalized === '') {
            return null;
        }

        return Brand::query()
            ->withoutGlobalScopes()
            ->where('custom_domain', $normalized)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createForCompany(Company $company, array $attributes): Brand
    {
        return Brand::query()->create([
            ...$attributes,
            'company_id' => $company->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Brand $brand, array $attributes): Brand
    {
        $brand->fill($attributes);
        $brand->save();

        return $brand->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upsertForCompany(Company $company, array $attributes): Brand
    {
        $existing = $this->findForCompany($company);

        if ($existing === null) {
            return $this->createForCompany($company, $attributes);
        }

        return $this->update($existing, $attributes);
    }
}
