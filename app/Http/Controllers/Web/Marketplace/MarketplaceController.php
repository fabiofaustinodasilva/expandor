<?php

namespace App\Http\Controllers\Web\Marketplace;

use App\Domains\Marketplace\Services\MarketplaceAnalyticsService;
use App\Domains\Marketplace\Services\MarketplacePublicPageService;
use App\Domains\Marketplace\Services\MarketplaceSettingsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function __construct(
        protected MarketplacePublicPageService $landing,
        protected MarketplaceAnalyticsService $analytics,
        protected MarketplaceSettingsService $settings,
    ) {}

    public function home(): View
    {
        $data = $this->landing->assemble();
        $this->analytics->record(MarketplaceAnalyticsService::PAGE_VIEW);

        return view('marketplace.landing', array_merge($data, [
            'preview' => false,
        ]));
    }

    public function plans(): View
    {
        $page = $this->landing->assemble();

        return view('marketplace.plans', [
            'premiumData' => $page['premium'],
            'settings' => $page['settings'],
            'uiCopy' => $page['ui'],
        ]);
    }

    public function subscribe(Request $request): RedirectResponse
    {
        unset($request);

        return redirect()->to(route('marketplace.home').'#demo');
    }

    public function sitemap(): \Illuminate\Http\Response
    {
        $urls = [
            url('/'),
            route('marketplace.plans'),
            route('signup.create'),
            route('login'),
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $loc) {
            $xml .= '<url><loc>'.e($loc).'</loc><changefreq>weekly</changefreq></url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
