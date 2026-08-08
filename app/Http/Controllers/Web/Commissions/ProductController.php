<?php

namespace App\Http\Controllers\Web\Commissions;

use App\Domains\Commissions\Models\SalesCommission;
use App\Domains\Sales\Products\Models\Product;
use App\Domains\Sales\Products\Models\StockMovement;
use App\Domains\Sales\Products\Services\ProductCatalogService;
use App\Domains\Sales\Products\Services\StockService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Commissions\StoreProductRequest;
use App\Http\Requests\Commissions\UpdateProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        protected ProductCatalogService $catalog,
        protected StockService $stock,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);
        abort_unless($request->user()?->hasPermission('commissions.manage'), 403);

        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $products = Product::query()
            ->withCount([
                'salesCommissions as sold_in_period' => fn ($q) => $q
                    ->whereDate('earned_at', '>=', $dateFrom)
                    ->whereDate('earned_at', '<=', $dateTo),
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $lowStock = $products->filter(fn (Product $p) => $p->isBelowMinimum());

        $recentMovements = StockMovement::query()
            ->with(['product', 'user'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('commissions.products.index', [
            'products' => $products,
            'lowStock' => $lowStock,
            'recentMovements' => $recentMovements,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('commissions.products.form', [
            'product' => new Product([
                'status' => Product::STATUS_ACTIVE,
                'stock_control' => false,
                'commission_amount' => 0,
                'price' => 0,
                'stock_quantity' => 0,
                'minimum_stock' => 0,
                'sort_order' => 0,
            ]),
            'editing' => false,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);
        $this->catalog->create($request->validated(), $request->user(), $request->file('image'));

        return redirect()
            ->route('commissions.products.index')
            ->with('success', $request->hasFile('image')
                ? 'Upload concluído. Produto cadastrado com imagem.'
                : 'Produto cadastrado.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('commissions.products.form', [
            'product' => $product,
            'editing' => true,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $this->catalog->update(
            $product,
            $request->validated(),
            $request->user(),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        $message = 'Produto atualizado.';
        if ($request->hasFile('image')) {
            $message = 'Upload concluído. Imagem do produto atualizada.';
        } elseif ($request->boolean('remove_image')) {
            $message = 'Remoção concluída. Imagem do produto removida.';
        }

        return redirect()
            ->route('commissions.products.index')
            ->with('success', $message);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        $this->catalog->deleteOrFail($product, request()->user());

        return redirect()
            ->route('commissions.products.index')
            ->with('success', 'Produto excluído.');
    }

    public function stockEntry(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('manageStock', $product);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->stock->entry($product, (int) $data['quantity'], $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Entrada de estoque registrada.');
    }

    public function stockAdjust(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('manageStock', $product);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'not_in:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->stock->adjustment($product, (int) $data['quantity'], $request->user(), $data['notes'] ?? null);

        return back()->with('success', 'Ajuste de estoque registrado.');
    }
}
