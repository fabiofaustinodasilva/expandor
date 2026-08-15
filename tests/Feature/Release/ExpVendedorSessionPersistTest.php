<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

class ExpVendedorSessionPersistTest extends TestCase
{
    public function test_session_persists_via_capacitor_preferences_not_password(): void
    {
        $pkg = (string) file_get_contents(base_path('package.json'));
        $storage = (string) file_get_contents(resource_path('js/mobile/secure-auth-storage.js'));
        $auth = (string) file_get_contents(resource_path('js/mobile/mobile-auth-service.js'));
        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $api = (string) file_get_contents(resource_path('js/mobile/api-fetch.js'));
        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));

        $this->assertStringContainsString('"@capacitor/preferences"', $pkg);
        $this->assertStringContainsString("nativePlugin('Preferences')", $storage);
        $this->assertStringContainsString('expandor.auth.token', $storage);
        $this->assertStringContainsString('expandor.auth.device_id', $storage);
        $this->assertStringContainsString('expandor.auth.session_version', $storage);
        $this->assertStringContainsString('setSessionVersion', $storage);
        $this->assertStringContainsString('ensureDeviceId', $storage);
        $this->assertStringContainsString('clearAuth', $storage);
        $this->assertStringNotContainsString('password', strtolower($storage));
        $this->assertDoesNotMatchRegularExpression('/localStorage\.(get|set|remove)/', $storage);
        $this->assertDoesNotMatchRegularExpression('/sessionStorage\.(get|set|remove)/', $storage);

        $this->assertStringContainsString('SecureAuthStorage.setToken(extracted.token)', $auth);
        $this->assertStringContainsString('setSessionVersion', $auth);
        $this->assertStringContainsString('/api/mobile/v1/me', $auth);
        $this->assertStringContainsString('/api/mobile/v1/logout', $auth);
        $this->assertStringContainsString('Sua sessão expirou. Entre novamente.', $auth);
        $this->assertStringContainsString('account_inactive', $auth);
        $this->assertStringContainsString('company_inactive', $auth);
        $this->assertStringContainsString('network_offline', $api);
        $this->assertStringNotContainsString('clearLocalAuth', $api);

        $this->assertStringContainsString('restoreSession', $shell);
        $this->assertStringContainsString('getCurrentUser', $shell);
        $this->assertStringContainsString('network_offline', $shell);
        $this->assertStringContainsString('showRestoreNetworkError', $shell);
        $this->assertStringContainsString('restore-retry', $shell);
        $this->assertStringContainsString('Entrando...', $shell);
        $this->assertStringNotContainsString('clearLocalAuth', explode('restoreSession', $shell)[1] ?? '');

        $this->assertStringContainsString('id="screen-restore"', $prepare);
        $this->assertStringContainsString('Entrando...', $prepare);
        $this->assertStringContainsString('Tentar novamente', $prepare);
        $this->assertStringContainsString('id="screen-login" hidden', $prepare);
    }
}
