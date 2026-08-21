<?php

namespace Tests\Support;

trait PlatformCompanyStorePayload
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function platformCompanyStorePayload(int $planId, array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Empresa Comercial QA',
            'legal_name' => 'Empresa Comercial QA LTDA',
            'document' => (string) random_int(10000000000000, 99999999999999),
            'company_email' => 'contato-'.uniqid().'@qa.expandor.test',
            'company_phone' => '11999990000',
            'company_city' => 'São Paulo',
            'company_uf' => 'SP',
            'plan_id' => $planId,
            'contract_started_at' => now()->toDateString(),
            'billing_day' => 10,
            'fidelity_mode' => '6',
            'admin_name' => 'Admin Comercial',
            'admin_email' => 'admin-'.uniqid().'@qa.expandor.test',
            'admin_phone' => '11988887777',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ], $overrides);
    }
}
