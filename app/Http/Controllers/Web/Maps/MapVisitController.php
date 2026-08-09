<?php

namespace App\Http\Controllers\Web\Maps;

use App\Domains\Campaigns\Models\Campaign;
use App\Domains\Visits\Actions\RegisterVisitAction;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Requests\StoreVisitRequest;
use App\Domains\Visits\Services\VisitService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MapVisitController extends Controller
{
    public function store(
        StoreVisitRequest $request,
        Campaign $campaign,
        RegisterVisitAction $action,
        VisitService $visits,
    ): JsonResponse {
        $this->authorize('create', Visit::class);

        $data = $request->validated();
        $visit = $action->execute($campaign, $data, $request->user());

        // Sprint 8.2.16 — Retorno sempre gera FollowUp (Agenda). Validação exige follow_up_at.
        if (($data['status'] ?? null) === VisitStatus::RETURN_LATER->value) {
            $visits->scheduleFollowUp($visit, [
                'scheduled_at' => $data['follow_up_at'],
                'notes' => $data['notes'] ?? null,
            ], $request->user());
        }

        return response()->json([
            'success' => true,
            'message' => 'Visita registrada',
            'data' => [
                'visit_id' => $visit->id,
                'property_id' => $visit->property_id,
                'status' => $visit->status->value,
                'plan' => $visit->plan,
                'follow_up_url' => route('visits.follow-ups.create', $visit),
                'show_url' => route('visits.show', $visit),
            ],
        ], 201);
    }
}
