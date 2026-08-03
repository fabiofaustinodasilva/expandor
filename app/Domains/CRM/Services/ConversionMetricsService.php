<?php

namespace App\Domains\CRM\Services;

use App\Domains\Analytics\DTOs\AnalyticsFiltersDTO;
use App\Domains\Analytics\Repositories\AnalyticsRepository;
use App\Domains\CRM\DTOs\ConversionMetricsDTO;
use App\Domains\CRM\Enums\LeadStatus;
use App\Domains\CRM\Enums\OpportunityStatus;
use App\Domains\CRM\Repositories\LeadRepository;
use App\Domains\CRM\Repositories\OpportunityRepository;
use App\Domains\Visits\Enums\VisitStatus;

class ConversionMetricsService
{
    public function __construct(
        protected LeadRepository $leads,
        protected OpportunityRepository $opportunities,
        protected RankingService $ranking,
        protected AnalyticsRepository $analytics,
        protected PipelineService $pipeline,
    ) {}

    public function metrics(?string $from = null, ?string $to = null): ConversionMetricsDTO
    {
        $this->pipeline->ensureDefaultStages();

        $from ??= now()->startOfMonth()->toDateString();
        $to ??= now()->endOfMonth()->toDateString();

        $leadsByStatus = $this->leads->countByStatus();
        $leadsTotal = array_sum($leadsByStatus);
        $leadsQualified = (int) ($leadsByStatus[LeadStatus::QUALIFIED->value] ?? 0)
            + (int) ($leadsByStatus[LeadStatus::CONVERTED->value] ?? 0);
        $leadsConverted = (int) ($leadsByStatus[LeadStatus::CONVERTED->value] ?? 0);

        $oppByStatus = $this->opportunities->countByStatus();
        $open = (int) ($oppByStatus[OpportunityStatus::OPEN->value] ?? 0);
        $won = (int) ($oppByStatus[OpportunityStatus::WON->value] ?? 0);
        $lost = (int) ($oppByStatus[OpportunityStatus::LOST->value] ?? 0);
        $closed = $won + $lost;

        $stageCounts = $this->opportunities->countByStage();
        $stages = $this->pipeline->stages();
        $byStage = [];
        foreach ($stages as $stage) {
            $byStage[$stage->name] = (int) ($stageCounts[$stage->id] ?? 0);
        }

        // Integra com Analytics (visitas) sem alterar o DTO existente do dashboard.
        $filters = new AnalyticsFiltersDTO(
            date_from: $from,
            date_to: $to,
            city_id: null,
            sector_id: null,
            user_id: null,
        );
        $visitInstallations = $this->analytics->countVisitsByStatus(
            $filters,
            VisitStatus::INSTALLATION_REQUESTED
        );

        return new ConversionMetricsDTO(
            leads_total: $leadsTotal,
            leads_qualified: $leadsQualified,
            leads_converted: $leadsConverted,
            lead_conversion_rate: $leadsTotal > 0 ? round(($leadsConverted / $leadsTotal) * 100, 1) : 0.0,
            opportunities_open: $open,
            opportunities_won: $won,
            opportunities_lost: $lost,
            opportunity_win_rate: $closed > 0 ? round(($won / $closed) * 100, 1) : 0.0,
            pipeline_amount: $this->opportunities->sumOpenAmount(),
            won_amount: $this->opportunities->sumWonAmount($from, $to),
            visit_installations: $visitInstallations,
            leads_by_status: $leadsByStatus,
            opportunities_by_stage: $byStage,
            seller_ranking: $this->ranking->sellerRanking($from, $to),
        );
    }
}
