<?php

namespace App\Http\Controllers\Web\SalesApp;

use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Services\ProductCatalogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesAppProductController extends Controller
{
    public function __construct(
        protected ProductCatalogService $catalog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeSalesApp($request);
        $this->authorize('viewAny', Product::class);

        $search = trim((string) $request->query('q', ''));
        $products = $this->catalog->activeCatalogForSeller($search !== '' ? $search : null);

        return view('sales-app.products.index', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    public function show(Request $request, Product $product): View
    {
        $this->authorizeSalesApp($request);
        $this->authorize('view', $product);

        $siblings = $this->catalog->activeCatalogForSeller();
        $ids = $siblings->pluck('id')->values();
        $index = $ids->search($product->id);
        $prev = $index !== false && $index > 0 ? $siblings[$index - 1] : null;
        $next = $index !== false && $index < $siblings->count() - 1 ? $siblings[$index + 1] : null;

        return view('sales-app.products.show', [
            'product' => $product,
            'prev' => $prev,
            'next' => $next,
        ]);
    }

    protected function authorizeSalesApp(Request $request): void
    {
        abort_unless(
            $request->user()?->hasPermission('sales_app.access') ?? false,
            403,
            'Access denied.'
        );
    }
}
