<?php

namespace App\Http\Controllers\Web\Maps;

use App\Domains\Commissions\Support\CommissionAwardedPayload;
use App\Domains\Sales\Properties\Models\Property;
use App\Domains\Visits\Actions\RegisterFirstApproachAction;
use App\Domains\Visits\Enums\VisitStatus;
use App\Domains\Visits\Models\Visit;
use App\Domains\Visits\Requests\StoreFirstApproachRequest;
use App\Http\Controllers\Controller;
use App\Support\CommercialTerminology;
use Illuminate\Http\JsonResponse;

class MapFirstApproachController extends Controller
{
    public function store(
        StoreFirstApproachRequest $request,
        RegisterFirstApproachAction $action,
    ): JsonResponse {
        $this->authorize('create', Property::class);
        $this->authorize('create', Visit::class);

        $result = $action->execute($request->validated(), $request->user());
        $property = $result['property'];
        $visit = $result['visit'];
        $resident = $property->residents->sortByDesc('is_primary_contact')->first()
            ?? $property->residents->first();

        $data = [
            'property_id' => $property->id,
            'visit_id' => $visit->id,
            'latitude' => (float) $property->latitude,
            'longitude' => (float) $property->longitude,
            'status' => $property->status->value,
            'status_label' => CommercialTerminology::propertyStatusLabel($property->status),
            'visit_status' => $visit->status->value,
            'plan' => $visit->plan,
            'address' => trim(($property->address?->street ?? '').' '.($property->address?->number ?? '')),
            'resident_name' => $resident?->name,
            'resident_phone' => $resident?->phone,
            'campaign_id' => $visit->campaign_id,
            'follow_up_url' => route('visits.follow-ups.create', $visit),
            'first_approach' => true,
        ];

        if ($visit->status === VisitStatus::INSTALLATION_REQUESTED) {
            $awarded = CommissionAwardedPayload::fromVisit($visit->fresh(['sale.items']));
            if ($awarded !== null) {
                $data['commission_awarded'] = $awarded;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Atendimento registrado',
            'data' => $data,
        ], 201);
    }
}
