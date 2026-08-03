<?php

namespace App\Http\Controllers\Web\Operations;

use App\Domains\Visits\Repositories\VisitRepository;
use App\Domains\Visits\Support\VisitHistoryPresenter;
use App\Http\Controllers\Controller;
use App\Support\CommercialTerminology;
use Illuminate\View\View;

class MyVisitsController extends Controller
{
    public function __construct(
        protected VisitRepository $visits
    ) {}

    public function __invoke(): View
    {
        abort_unless(
            auth()->user()?->hasPermission('visits.view') ?? false,
            403,
            'Access denied.'
        );

        /** @var \App\Domains\Company\Models\User $user */
        $user = auth()->user();

        $visits = $this->visits->paginateMyVisits($user);
        $propertyFollowUps = $this->visits->pendingFollowUpsByPropertyForUser(
            $user,
            $visits->getCollection()->pluck('property_id')->all()
        );

        $cards = $visits->getCollection()->map(function ($visit) use ($propertyFollowUps, $user) {
            $resident = VisitHistoryPresenter::primaryResident($visit);
            $nextFollowUp = VisitHistoryPresenter::resolveNextFollowUp($visit, $propertyFollowUps);
            $phoneDigits = VisitHistoryPresenter::phoneDigits($resident);
            $lat = $visit->property?->latitude ?? $visit->latitude;
            $lng = $visit->property?->longitude ?? $visit->longitude;
            $addressLabel = VisitHistoryPresenter::displayAddress($visit->property?->address);
            $clientTitle = VisitHistoryPresenter::clientTitle($visit);
            $phoneDisplay = VisitHistoryPresenter::phoneDisplay($resident);

            $waHref = null;
            if ($phoneDigits !== '') {
                $waText = rawurlencode(
                    'Olá'.($resident?->name ? ' '.$resident->name : '').'! Sou '.$user->name.'. Sobre nosso atendimento.'
                );
                $waHref = "https://wa.me/{$phoneDigits}?text={$waText}";
            }

            $routeHref = ($lat !== null && $lng !== null)
                ? "https://www.google.com/maps/dir/?api=1&destination={$lat},{$lng}"
                : null;

            return [
                'id' => $visit->id,
                'visited_at' => $visit->visited_at?->format('d/m H:i') ?? '—',
                'visited_at_full' => $visit->visited_at?->format('d/m/Y H:i') ?? '—',
                'client' => $clientTitle,
                'address' => $addressLabel,
                'phone' => $phoneDisplay,
                'phone_digits' => $phoneDigits,
                'result' => CommercialTerminology::visitResult($visit->status),
                'campaign' => $visit->campaign?->name ?? 'Sem campanha',
                'seller' => $visit->user?->name ?? $user->name,
                'notes' => $visit->notes,
                'plan' => $visit->plan,
                'next_action' => VisitHistoryPresenter::nextActionLabel($nextFollowUp),
                'next_action_short' => VisitHistoryPresenter::nextActionShort($nextFollowUp),
                'next_time_hint' => $nextFollowUp?->scheduleTimeHint(),
                'next_return_label' => $nextFollowUp
                    ? $nextFollowUp->scheduleLabel()
                    : VisitHistoryPresenter::NO_NEXT_STEP,
                'wa_href' => $waHref,
                'route_href' => $routeHref,
                'new_follow_up_url' => route('visits.follow-ups.create', $visit),
                'map_url' => $visit->property_id
                    ? route('map.index', ['property' => $visit->property_id])
                    : route('map.index'),
            ];
        });

        $visits->setCollection($cards);

        return view('operations.my-visits', [
            'visits' => $visits,
        ]);
    }
}
