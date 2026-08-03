<?php

namespace App\Support;

use App\Domains\Company\Enums\CompanySegment;
use App\Domains\Sales\Properties\Enums\PropertyStatus;
use App\Domains\Visits\Enums\VisitStatus;

/**
 * Camada única de terminologia comercial (UX).
 *
 * Os valores internos (ex.: installation_requested) e os labels formais dos
 * enums permanecem inalterados. Esta classe controla apenas o texto exibido
 * ao usuário. O segmento da empresa pode alterar substantivos de apresentação.
 */
final class CommercialTerminology
{
    public static function segment(): ?CompanySegment
    {
        $raw = auth()->user()?->company?->segment;

        return is_string($raw) ? CompanySegment::tryFrom($raw) : null;
    }

    /**
     * Substantivo do item comercial (Plano / Produto / Serviço…).
     */
    public static function offeringNoun(): string
    {
        return match (self::segment()) {
            CompanySegment::INTERNET => 'Plano',
            CompanySegment::SOLAR => 'Sistema',
            CompanySegment::SECURITY => 'Solução',
            CompanySegment::DOOR_TO_DOOR => 'Produto',
            default => 'Produto',
        };
    }

    /**
     * Substantivo da conversão comercial (Instalação / Venda).
     */
    public static function conversionNoun(): string
    {
        return match (self::segment()) {
            CompanySegment::INTERNET => 'Instalação',
            CompanySegment::SOLAR => 'Instalação',
            CompanySegment::DOOR_TO_DOOR => 'Venda',
            default => 'Venda',
        };
    }

    public static function customerNoun(): string
    {
        return 'Cliente';
    }

    public static function saleCompleted(): string
    {
        return match (self::segment()) {
            CompanySegment::INTERNET,
            CompanySegment::SOLAR => self::conversionNoun().' realizada',
            default => 'Venda realizada',
        };
    }

    public static function saleCompletedBadge(): string
    {
        return '🟢 '.self::saleCompleted();
    }

    public static function saleRegisteredToast(): string
    {
        return match (self::segment()) {
            CompanySegment::INTERNET,
            CompanySegment::SOLAR => self::conversionNoun().' registrada',
            default => 'Venda registrada',
        };
    }

    public static function sales(): string
    {
        return match (self::segment()) {
            CompanySegment::INTERNET,
            CompanySegment::SOLAR => self::conversionNoun().'s',
            default => 'Vendas',
        };
    }

    public static function salesOfDay(): string
    {
        return self::sales().' do dia';
    }

    public static function totalSales(): string
    {
        return 'Total de '.mb_strtolower(self::sales());
    }

    public static function salesWon(): string
    {
        return self::sales();
    }

    public static function salesRequestedMeta(): string
    {
        return self::conversionNoun().'s realizadas';
    }

    public static function salesPerVisits(): string
    {
        return self::sales().' / visitas';
    }

    public static function avgSalesPerSeller(): string
    {
        return 'Média '.mb_strtolower(self::sales()).' / vendedor';
    }

    public static function commissionPerSale(): string
    {
        return 'Comissão por '.mb_strtolower(self::conversionNoun());
    }

    public static function newSales(): string
    {
        return 'Novas '.mb_strtolower(self::sales());
    }

    public static function salesAnalytics(): string
    {
        return self::sales().' (Analytics)';
    }

    /**
     * Label comercial do resultado da visita (histórico, agenda, cards).
     */
    public static function visitResult(?VisitStatus $status): string
    {
        if ($status === null) {
            return '—';
        }

        return match ($status) {
            VisitStatus::INSTALLATION_REQUESTED => self::saleCompletedBadge(),
            default => $status->commercialLabel(),
        };
    }

    /**
     * Label de filtro/gráfico para status de visita (sem emoji).
     */
    public static function visitStatusLabel(VisitStatus $status): string
    {
        return match ($status) {
            VisitStatus::INSTALLATION_REQUESTED => self::saleCompleted(),
            default => $status->label(),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function visitStatusOptions(): array
    {
        $options = [];

        foreach (VisitStatus::cases() as $case) {
            $options[$case->value] = self::visitStatusLabel($case);
        }

        return $options;
    }

    /**
     * Opções de desfecho na agenda (completo follow-up).
     *
     * @return array<string, string>
     */
    public static function agendaOutcomeOptions(): array
    {
        return [
            VisitStatus::INSTALLATION_REQUESTED->value => self::saleCompletedBadge(),
            VisitStatus::INTERESTED->value => VisitStatus::INTERESTED->commercialLabel(),
            VisitStatus::RETURN_LATER->value => '🟡 Retornar',
            VisitStatus::NO_INTEREST->value => VisitStatus::NO_INTEREST->commercialLabel(),
            VisitStatus::NOT_HOME->value => VisitStatus::NOT_HOME->commercialLabel(),
        ];
    }

    public static function propertyStatusLabel(PropertyStatus $status): string
    {
        return match ($status) {
            PropertyStatus::INSTALLATION_REQUESTED => self::saleCompleted(),
            default => $status->label(),
        };
    }

    /**
     * Apresentação comercial do cliente (CRM) — sem alterar enums.
     */
    public static function customerSituation(
        PropertyStatus $status,
        ?VisitStatus $lastVisitStatus = null,
    ): string {
        if ($lastVisitStatus === VisitStatus::NOT_HOME
            && in_array($status, [PropertyStatus::NEW], true)) {
            return '⚫ Não encontrado';
        }

        $customer = self::customerNoun();

        return match ($status) {
            PropertyStatus::CUSTOMER,
            PropertyStatus::INSTALLATION_REQUESTED => '🟢 '.$customer,
            PropertyStatus::INTERESTED => '🟡 Interessado',
            PropertyStatus::RETURN_LATER => '🟠 Retorno marcado',
            PropertyStatus::NO_INTEREST => '🔴 Sem interesse',
            PropertyStatus::NEW => '⚪ Visitado',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function propertyStatusOptions(): array
    {
        $options = [];

        foreach (PropertyStatus::cases() as $case) {
            $options[$case->value] = self::propertyStatusLabel($case);
        }

        return $options;
    }
}
