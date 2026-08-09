<?php

namespace App\Http\Controllers\Web\SalesApp;

use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Services\ProductCatalogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesAppProductController extends Controller
{
    public function __construct(
        protected ProductCatalogService $catalog,
    ) {}

    /**
     * Entry: list + CTA into presentation mode.
     */
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

    /**
     * Full-screen commercial presentation (swipe deck).
     * Loads catalog once as JSON — navigation is client-side.
     */
    public function present(Request $request): View
    {
        $this->authorizeSalesApp($request);
        $this->authorize('viewAny', Product::class);

        $products = $this->catalog->activeCatalogForSeller();
        $startId = (int) $request->query('product', 0);
        $startIndex = 0;
        if ($startId > 0) {
            $found = $products->search(fn (Product $p) => (int) $p->id === $startId);
            if ($found !== false) {
                $startIndex = (int) $found;
            }
        }

        $payload = $products->values()->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->categoryLabel(),
            'description' => (string) ($p->description ?? ''),
            'benefits' => $p->benefitList(),
            'price' => number_format((float) $p->price, 2, ',', '.'),
            'image' => $p->imageOriginalUrl() ?: $p->imageUrl(),
            'video' => $p->embeddableVideoUrl(),
            'video_embed' => $p->embeddableVideoUrl() !== null
                && (str_contains((string) $p->embeddableVideoUrl(), 'youtube.com/embed')
                    || str_contains((string) $p->embeddableVideoUrl(), 'player.vimeo.com')),
        ])->all();

        return view('sales-app.products.present', [
            'deck' => $payload,
            'startIndex' => $startIndex,
            'mapUrl' => route('map.index'),
            // Deep-link into existing map FirstApproach / sale finalize (no parallel form).
            'contractUrlBase' => route('map.index').'?contract_product=',
        ]);
    }

    public function show(Request $request, Product $product): RedirectResponse
    {
        // Prefer presentation deck; keep show as redirect into present for deep links.
        $this->authorizeSalesApp($request);
        $this->authorize('view', $product);

        return redirect()->route('sales-app.products.present', ['product' => $product->id]);
    }

    protected function authorizeSalesApp(Request $request): void
    {
        $user = $request->user();
        abort_unless(
            ($user?->hasPermission('sales_app.access') ?? false)
            || ($user?->hasPermission('commissions.manage') ?? false),
            403,
            'Access denied.'
        );
    }
}
