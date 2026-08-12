<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Hotfix — paintCampaignInto must remain top-level (ReferenceError no bootstrap).
 */
class ExpVendedorBootstrapCampaignFixTest extends TestCase
{
    public function test_debug_flow_is_properly_closed_before_helpers(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));

        $this->assertMatchesRegularExpression(
            '/function debugFlow\([^)]*\)\s*\{\s*try\s*\{[\s\S]*?\}\s*catch\s*\{[\s\S]*?\}\s*\}\s*function renderOutcomeButtons/',
            $shell,
            'debugFlow must close before renderOutcomeButtons (missing } nests campaign helpers)',
        );
    }

    public function test_campaign_helpers_are_module_level_functions(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));

        foreach ([
            'paintCampaignInto',
            'paintCampaignContext',
            'renderOutcomeButtons',
            'selectCreateOutcome',
            'syncCreateOutcomeBlocks',
            'resolveCampaignId',
            'hydrateCatalog',
            'tryInitialMapGps',
            'enterApp',
            'startup',
        ] as $fn) {
            $this->assertMatchesRegularExpression(
                '/^(async )?function '.$fn.'\(/m',
                $shell,
                "{$fn} must be a module-level function declaration",
            );
        }
    }

    public function test_bootstrap_calls_campaign_paint_after_enter(): void
    {
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));

        $this->assertStringContainsString('paintCampaignContext()', $shell);
        $this->assertStringContainsString("paintCampaignInto('create')", $shell);
        $this->assertStringContainsString("paintCampaignInto('visit')", $shell);
        $this->assertStringContainsString('void tryInitialMapGps()', $shell);
        $this->assertStringContainsString('MapAdapter.init', $shell);
    }

    public function test_built_bundle_keeps_campaign_call_sites_when_present(): void
    {
        $bundle = public_path('capacitor-shell/vendor/seller-app.js');
        if (! is_file($bundle)) {
            $this->markTestSkipped('capacitor-shell not built');
        }

        $js = (string) file_get_contents($bundle);

        // Call-site string literals survive minify; name may be mangled.
        $this->assertStringContainsString('campaign_context', $js);
        $this->assertStringContainsString('campaign-label', $js);
        $this->assertStringContainsString('no_campaign_message', $js);
        $this->assertStringContainsString('requires_selection', $js);
        $this->assertStringContainsString('active_campaign_id', $js);

        // Must not leave an unresolved identifier as a naked global call in source map style —
        // presence of campaign paint logic markers proves the helper body was emitted.
        $this->assertTrue(
            str_contains($js, 'paintCampaignInto')
            || (str_contains($js, 'campaign-warning') && str_contains($js, 'has_campaign')),
            'Bundle must contain campaign paint implementation',
        );
    }
}
