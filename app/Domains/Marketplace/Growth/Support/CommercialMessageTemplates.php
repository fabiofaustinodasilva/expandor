<?php

namespace App\Domains\Marketplace\Growth\Support;

use App\Domains\Marketplace\Growth\Models\MarketplaceLead;
use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Revenue\Models\MarketplaceSalesPipeline;

final class CommercialMessageTemplates
{
    public static function defaultAlert(): string
    {
        return <<<'TXT'
🚀 NOVO PEDIDO DE DEMONSTRAÇÃO — EXPANDOR
Nome: {nome}
Provedor: {provedor}
Cidade: {cidade}/{uf}
WhatsApp: {whatsapp}
Vendedores: {vendedores}
Clientes: {clientes}
Origem: {origem}
Campanha: {campanha}
Lead recebido às {hora}.
Acesse a Central Comercial do Expandor para atender.
TXT;
    }

    public static function defaultOutreach(): string
    {
        return 'Olá, {primeiro_nome}! Tudo bem? Aqui é {responsavel}, do Expandor. Vi que você solicitou uma demonstração do sistema. Qual horário fica melhor para conversarmos?';
    }

    public static function defaultSchedule(): string
    {
        return 'Olá, {primeiro_nome}! Nossa demonstração do Expandor ficou agendada para {data_demo} às {hora_demo}. Até lá!';
    }

    public static function visitorDemoRequest(MarketplaceLead $lead): string
    {
        $customers = $lead->customers_count !== null
            ? number_format((int) $lead->customers_count, 0, ',', '.')
            : '—';

        return trim(implode("\n", [
            'Olá! Acabei de solicitar uma demonstração do Expandor pelo site.',
            'Meu nome é '.($lead->name ?: '—').'.',
            'Provedor: '.($lead->company_name ?: '—'),
            'Cidade: '.($lead->city ?: '—').'/'.($lead->state ?: '—'),
            'Vendedores externos: '.($lead->sellers_count !== null ? (string) $lead->sellers_count : '—'),
            'Clientes aproximados: '.$customers,
            'Gostaria de conhecer o Expandor.',
        ]));
    }

    public static function render(
        string $template,
        MarketplaceLead $lead,
        ?MarketplaceSetting $settings = null,
        ?MarketplaceSalesPipeline $pipeline = null,
    ): string {
        $origin = CommercialOrigin::present($lead);
        $responsavel = trim((string) ($settings?->commercial_owner_name ?? ''));
        if ($responsavel === '') {
            $responsavel = 'o time';
        }

        $demoAt = $pipeline?->demo_scheduled_at;

        $map = [
            '{primeiro_nome}' => $lead->firstName(),
            '{nome}' => $lead->name,
            '{provedor}' => $lead->company_name ?: '—',
            '{cidade}' => $lead->city ?: '—',
            '{uf}' => $lead->state ?: '—',
            '{whatsapp}' => BrazilianPhone::format($lead->phone) ?: ($lead->phone ?: '—'),
            '{vendedores}' => $lead->sellers_count !== null ? (string) $lead->sellers_count : '—',
            '{clientes}' => $lead->customers_count !== null ? number_format((int) $lead->customers_count, 0, ',', '.') : '—',
            '{origem}' => $origin['origin'],
            '{campanha}' => $origin['campaign'] ?: '—',
            '{hora}' => optional($lead->created_at)?->timezone(config('app.timezone'))?->format('H:i') ?: now()->format('H:i'),
            '{responsavel}' => $responsavel,
            '{data_demo}' => $demoAt?->timezone(config('app.timezone'))->format('d/m/Y') ?: '—',
            '{hora_demo}' => $demoAt?->timezone(config('app.timezone'))->format('H:i') ?: '—',
        ];

        return trim(strtr($template, $map));
    }
}
