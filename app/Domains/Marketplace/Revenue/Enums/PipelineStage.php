<?php

namespace App\Domains\Marketplace\Revenue\Enums;

enum PipelineStage: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case DemoScheduled = 'demo_scheduled';
    case DemoCompleted = 'demo_completed';
    case ProposalSent = 'proposal_sent';
    case Negotiation = 'negotiation';
    case TrialStarted = 'trial_started';
    case Customer = 'customer';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Novo',
            self::Contacted => 'Contato realizado',
            self::DemoScheduled => 'Demonstração agendada',
            self::DemoCompleted => 'Demonstração realizada',
            self::ProposalSent => 'Proposta enviada',
            self::Negotiation => 'Negociação',
            self::TrialStarted => 'Trial iniciado',
            self::Customer => 'Fechado',
            self::Lost => 'Perdido',
        };
    }

    public function kanbanColumn(): string
    {
        return match ($this) {
            self::New => 'new',
            self::Contacted => 'contacted',
            self::DemoScheduled, self::DemoCompleted, self::TrialStarted => 'demo',
            self::ProposalSent => 'proposal',
            self::Negotiation => 'negotiation',
            self::Customer => 'closed',
            self::Lost => 'lost',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function kanbanColumns(): array
    {
        return [
            'new' => 'Novos',
            'contacted' => 'Contato',
            'demo' => 'Demonstração',
            'proposal' => 'Proposta',
            'negotiation' => 'Negociação',
            'closed' => 'Fechados',
        ];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
