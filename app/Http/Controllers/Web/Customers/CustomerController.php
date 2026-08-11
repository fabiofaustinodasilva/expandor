<?php

namespace App\Http\Controllers\Web\Customers;

use App\Domains\Customers\Policies\CustomerPolicy;
use App\Domains\Customers\Services\CustomerDeletionService;
use App\Domains\Customers\Services\CustomerQueryService;
use App\Domains\Sales\Properties\Models\Property;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerQueryService $customers,
        protected CustomerDeletionService $deletion,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null && app(CustomerPolicy::class)->viewAny($user), 403);

        $q = $request->string('q')->trim()->toString();
        $paginator = $this->customers->paginate($user, $q !== '' ? $q : null);
        $canManage = app(CustomerPolicy::class)->manage($user);

        $cards = $paginator->getCollection()
            ->map(function (Property $property) use ($user, $canManage) {
                $card = $this->customers->presentCard($property);
                $card['can_delete'] = $canManage
                    && app(CustomerPolicy::class)->delete($user, $property)
                    && ! $card['has_history'];
                $card['delete_blocked'] = $canManage && $card['has_history'];

                return $card;
            })
            ->values();

        return view('customers.index', [
            'customers' => $cards,
            'paginator' => $paginator,
            'q' => $q,
            'can_manage' => $canManage,
        ]);
    }

    public function show(Request $request, Property $property): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        abort_unless(app(CustomerPolicy::class)->view($user, $property), 403);

        $loaded = $this->customers->findForActor($user, (int) $property->id);
        abort_if($loaded === null, 404);

        $dossier = $this->customers->presentDossier($loaded);
        $canDelete = app(CustomerPolicy::class)->delete($user, $loaded);
        $blocked = $this->deletion->hasBlockingHistory($loaded);
        $dossier['can_delete'] = $canDelete && ! $blocked;
        $dossier['delete_blocked'] = $canDelete && $blocked;
        $dossier['delete_blocked_message'] = CustomerDeletionService::BLOCKED_MESSAGE;

        return view('customers.show', [
            'dossier' => $dossier,
        ]);
    }

    public function destroy(Request $request, Property $property): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        abort_unless(app(CustomerPolicy::class)->delete($user, $property), 403);

        $loaded = $this->customers->findForActor($user, (int) $property->id);
        abort_if($loaded === null, 404);

        $this->deletion->deleteOrFail($loaded, $user);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cliente excluído.');
    }
}
