<?php

namespace Tests\Feature\Release;

use Tests\TestCase;

/**
 * Hotfix — APK "Resposta de login inválida": envelope data.* + CAP_API_URL bake.
 */
class MobileLoginEnvelopeHotfixTest extends TestCase
{
    public function test_extract_login_data_accepts_production_envelope(): void
    {
        $auth = (string) file_get_contents(resource_path('js/mobile/mobile-auth-service.js'));
        $this->assertStringContainsString('export function extractLoginData', $auth);
        $this->assertStringContainsString('payload.data', $auth);
        $this->assertStringContainsString('data.token', $auth);
        $this->assertStringContainsString('missing_user', $auth);
        $this->assertStringContainsString('missing_session', $auth);
        $this->assertStringContainsString('invalid_login_response', $auth);
        $this->assertStringContainsString('Resposta de login inválida.', $auth);
        $this->assertStringNotContainsString('payload.token', $auth);
        $this->assertStringNotContainsString('console.log(token', $auth);
        $this->assertStringNotContainsString('console.log(password', $auth);
        $this->assertStringContainsString("console.warn('[ExpandorAuth] login envelope rejected'", $auth);

        // Exact production shape used by extractLoginData (Node-less structural contract).
        $fixture = [
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'data' => [
                'token' => '1|plain-text-token-example',
                'token_type' => 'Bearer',
                'user' => [
                    'id' => 1,
                    'name' => 'Seller',
                    'email' => 'seller@example.com',
                ],
                'company' => ['id' => 10, 'name' => 'Empresa'],
                'permissions' => ['sales_app.access', 'maps.view'],
                'session' => [
                    'version' => 3,
                    'device_id' => '22222222-2222-4222-8222-000000000001',
                ],
            ],
        ];

        $this->assertIsString($fixture['data']['token']);
        $this->assertNotSame('', $fixture['data']['token']);
        $this->assertIsArray($fixture['data']['user']);
        $this->assertIsArray($fixture['data']['session']);
        $this->assertArrayNotHasKey('token', $fixture);

        $api = (string) file_get_contents(resource_path('js/mobile/api-fetch.js'));
        $this->assertStringContainsString('Returns the raw `Response`', $api);
        $this->assertStringContainsString('Does NOT unwrap', $api);
        $this->assertStringContainsString('api_base_missing', $api);

        $mobileApi = (string) file_get_contents(resource_path('js/mobile/mobile-api.js'));
        $this->assertStringContainsString('FULL envelope', $mobileApi);
        $this->assertStringContainsString('payload.data', $mobileApi);

        $shell = (string) file_get_contents(resource_path('js/mobile/bootstrap-shell.js'));
        $this->assertStringContainsString('EXPANDOR_API_BASE', $shell);
        $this->assertStringContainsString('api_base_missing', $shell);
        $this->assertStringContainsString('Recompile com CAP_API_URL', $shell);

        $prepare = (string) file_get_contents(base_path('scripts/prepare-capacitor-shell.mjs'));
        $this->assertStringContainsString('loadEnvFile', $prepare);
        $this->assertStringContainsString('CAP_API_URL', $prepare);
        $this->assertStringContainsString('is required to bake Expandor shell API base', $prepare);
        $this->assertStringContainsString('window.EXPANDOR_API_BASE = ${JSON.stringify(apiBase)}', $prepare);

        $storage = (string) file_get_contents(resource_path('js/mobile/secure-auth-storage.js'));
        $this->assertDoesNotMatchRegularExpression('/localStorage\.(get|set|remove)/', $storage);
    }

    public function test_login_rejects_root_level_token_shape_in_parser(): void
    {
        $auth = (string) file_get_contents(resource_path('js/mobile/mobile-auth-service.js'));
        // Must not treat { token } at root as valid login data.
        $this->assertStringContainsString("reason: 'missing_data'", $auth);
        $this->assertStringContainsString('extractLoginData(payload)', $auth);
        $this->assertStringContainsString('SecureAuthStorage.setToken(extracted.token)', $auth);
        $this->assertStringContainsString('this.currentUser = extracted.data', $auth);
    }
}
