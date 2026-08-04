<?php

namespace App\Http\Controllers\Web\Platform\Marketplace;

use App\Domains\Marketplace\Actions\UpdateMarketplaceSettingsAction;
use App\Domains\Marketplace\Requests\UpdateMarketplaceSettingsRequest;
use App\Domains\Marketplace\Services\MarketplacePublicPageService;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MarketplaceSettingsController extends Controller
{
    public function edit(MarketplaceSettingsService $settings): View
    {
        $this->authorize('marketplace.manage');

        return view('platform.marketplace.settings', [
            'settings' => $settings->current(),
        ]);
    }

    public function update(
        UpdateMarketplaceSettingsRequest $request,
        UpdateMarketplaceSettingsAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->validated(),
            [
                'logo' => $request->file('logo'),
                'favicon' => $request->file('favicon'),
                'hero_image' => $request->file('hero_image'),
            ],
            [
                'logo' => $request->boolean('remove_logo'),
                'favicon' => $request->boolean('remove_favicon'),
                'hero_image' => $request->boolean('remove_hero_image'),
            ],
        );

        return redirect()
            ->route('platform.marketplace.settings.edit')
            ->with('success', 'Configuração do Marketplace salva.');
    }

    public function preview(MarketplacePublicPageService $page): View
    {
        $this->authorize('marketplace.manage');

        $data = $page->assemble(bypassCache: true);
        $data['preview'] = true;

        return view('marketplace.landing', $data);
    }
}
