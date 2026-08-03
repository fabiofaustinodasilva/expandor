<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class IntegrationsController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(
            auth()->user()?->hasPermission('integrations.view') ?? false,
            403,
            'Access denied.'
        );

        return view('operations.integrations', [
            'integrations' => [
                [
                    'key' => 'whatsapp',
                    'name' => 'WhatsApp',
                    'description' => 'Envio e histórico de mensagens por empresa.',
                    'status' => 'Em breve',
                    'icon' => 'message-circle',
                ],
                [
                    'key' => 'google_maps',
                    'name' => 'Google Maps',
                    'description' => 'Mapas e geocoding avançados por tenant.',
                    'status' => 'Em breve',
                    'icon' => 'map',
                ],
                [
                    'key' => 'webhooks',
                    'name' => 'Webhooks',
                    'description' => 'Notificações de eventos para sistemas externos.',
                    'status' => 'Em breve',
                    'icon' => 'webhook',
                ],
                [
                    'key' => 'external_apis',
                    'name' => 'APIs externas',
                    'description' => 'Conexões REST com parceiros e provedores.',
                    'status' => 'Em breve',
                    'icon' => 'plug',
                ],
                [
                    'key' => 'erp',
                    'name' => 'ERP',
                    'description' => 'Sincronização comercial e financeira.',
                    'status' => 'Em breve',
                    'icon' => 'building-2',
                ],
            ],
        ]);
    }
}
