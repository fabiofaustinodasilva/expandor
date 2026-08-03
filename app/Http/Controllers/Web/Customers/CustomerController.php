<?php

namespace App\Http\Controllers\Web\Customers;

use App\Domains\Customers\Policies\CustomerPolicy;
use App\Domains\Customers\Services\CustomerQueryService;
use App\Domains\Sales\Properties\Models\Property;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerQueryService $customers,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user !== null && app(CustomerPolicy::class)->viewAny($user), 403);

        $q = $request->string('q')->trim()->toString();
        $paginator = $this->customers->paginate($user, $q !== '' ? $q : null);

        $cards = $paginator->getCollection()
            ->map(fn (Property $property) => $this->customers->presentCard($property))
            ->values();

        return view('customers.index', [
            'customers' => $cards,
            'paginator' => $paginator,
            'q' => $q,
        ]);
    }

    public function show(Request $request, Property $property): View
    {
        $user = $request->user();
        abort_unless($user !== null, 403);
        abort_unless(app(CustomerPolicy::class)->view($user, $property), 403);

        $loaded = $this->customers->findForActor($user, (int) $property->id);
        abort_if($loaded === null, 404);

        return view('customers.show', [
            'dossier' => $this->customers->presentDossier($loaded),
        ]);
    }
}
