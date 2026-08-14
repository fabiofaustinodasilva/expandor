<?php

namespace App\Domains\Sales\Handoff;

use App\Domains\Company\Models\Company;
use App\Domains\Company\Models\CompanySetting;

final class OfficeSalesWhatsAppSettings
{
    public const NUMBER_KEY = 'office_sales_whatsapp';

    public const ENABLED_KEY = 'office_sales_whatsapp_enabled';

    /**
     * @return array{enabled: bool, number: string, digits: string}
     */
    public function forCompany(Company $company): array
    {
        $enabledFlag = $this->read($company->id, self::ENABLED_KEY) === '1';
        $number = trim((string) $this->read($company->id, self::NUMBER_KEY));
        if ($number === '') {
            $number = trim((string) ($company->whatsapp ?: $company->phone ?: ''));
        }
        $digits = $this->digits($number);

        return [
            'enabled_flag' => $enabledFlag,
            'enabled' => $enabledFlag && $digits !== '',
            'number' => $number,
            'digits' => $digits,
        ];
    }

    public function save(Company $company, ?string $number, bool $enabled): void
    {
        CompanySetting::query()->updateOrCreate(
            ['company_id' => $company->id, 'key' => self::NUMBER_KEY],
            ['value' => trim((string) $number)]
        );
        CompanySetting::query()->updateOrCreate(
            ['company_id' => $company->id, 'key' => self::ENABLED_KEY],
            ['value' => $enabled ? '1' : '0']
        );
    }

    public function digits(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        if ($digits === '') {
            return '';
        }
        if (! str_starts_with($digits, '55') && strlen($digits) >= 10 && strlen($digits) <= 11) {
            $digits = '55'.$digits;
        }

        return $digits;
    }

    protected function read(int $companyId, string $key): ?string
    {
        $value = CompanySetting::query()
            ->where('company_id', $companyId)
            ->where('key', $key)
            ->value('value');

        return $value === null ? null : (string) $value;
    }
}
