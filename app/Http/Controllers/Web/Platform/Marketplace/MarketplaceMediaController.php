<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Models\MarketplaceFaq;
use App\Domains\Marketplace\Models\MarketplaceMedia;
use App\Domains\Marketplace\Models\MarketplaceTestimonial;
use App\Domains\Marketplace\Services\MarketplaceMediaService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceMediaController extends Controller
{
    public function index(MarketplaceMediaService $media): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.media.index', [
            'items' => $media->all(),
            'testimonials' => MarketplaceTestimonial::query()->orderBy('order')->get(),
            'faqs' => MarketplaceFaq::query()->orderBy('order')->get(),
        ]);
    }

    public function store(Request $request, MarketplaceMediaService $media): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'type' => ['required', 'in:image,video'],
            'title' => ['nullable', 'string', 'max:180'],
            'caption' => ['nullable', 'string', 'max:500'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'file' => ['nullable', 'file', 'max:20480'],
            'thumbnail' => ['nullable', 'file', 'max:8192'],
        ]);

        $media->create(
            array_merge($validated, ['active' => $request->boolean('active', true)]),
            $request->file('file'),
            $request->file('thumbnail'),
        );

        return back()->with('success', 'Mídia adicionada.');
    }

    public function destroy(MarketplaceMedia $medium, MarketplaceMediaService $media): RedirectResponse
    {
        $this->authorize('marketplace.manage');
        $media->delete($medium);

        return back()->with('success', 'Mídia removida.');
    }

    public function storeTestimonial(Request $request, MarketplaceMediaService $media): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'text' => ['required', 'string', 'max:2000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'active' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'file', 'max:4096'],
        ]);

        $media->createTestimonial(
            array_merge($validated, ['active' => $request->boolean('active', true)]),
            $request->file('avatar'),
        );

        return back()->with('success', 'Depoimento adicionado.');
    }

    public function destroyTestimonial(MarketplaceTestimonial $testimonial, MarketplaceMediaService $media): RedirectResponse
    {
        $this->authorize('marketplace.manage');
        $media->deleteTestimonial($testimonial);

        return back()->with('success', 'Depoimento removido.');
    }

    public function storeFaq(Request $request, MarketplaceMediaService $media): RedirectResponse
    {
        $this->authorize('marketplace.manage');

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'order' => ['nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $media->createFaq(array_merge($validated, ['active' => $request->boolean('active', true)]));

        return back()->with('success', 'FAQ adicionado.');
    }

    public function destroyFaq(MarketplaceFaq $faq, MarketplaceMediaService $media): RedirectResponse
    {
        $this->authorize('marketplace.manage');
        $media->deleteFaq($faq);

        return back()->with('success', 'FAQ removido.');
    }
}
