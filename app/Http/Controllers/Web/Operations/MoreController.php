<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MoreController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(auth()->check(), 403);

        $user = auth()->user();
        $user?->loadMissing(['role.permissions', 'permissionOverrides', 'company']);

        return view('operations.more', [
            'company' => $user?->company,
            'links' => $this->links($user),
        ]);
    }

    /**
     * @return list<array{label: string, href: string, group: string}>
     */
    protected function links($user): array
    {
        $links = [
            ['group' => 'Conta', 'label' => 'Meu perfil', 'href' => route('profile.edit')],
        ];

        if ($user?->hasPermission('customers.view')) {
            $links[] = ['group' => 'Comercial', 'label' => 'Clientes', 'href' => route('customers.index')];
        }
        if ($user?->hasPermission('crm.view')) {
            $links[] = ['group' => 'Comercial', 'label' => 'CRM / Pipeline', 'href' => route('crm.dashboard')];
        }
        if ($user?->hasPermission('commissions.manage')) {
            $links[] = ['group' => 'Comercial', 'label' => '💰 Comissões', 'href' => route('commissions.index')];
            $links[] = ['group' => 'Comercial', 'label' => '📦 Produtos / Estoque', 'href' => route('commissions.products.index')];
        } elseif ($user?->hasPermission('commissions.view_self')) {
            $links[] = ['group' => 'Comercial', 'label' => '💰 Minha comissão', 'href' => route('commissions.index')];
        }
        if ($user?->hasPermission('properties.view')) {
            $links[] = ['group' => 'Território', 'label' => 'Clientes / Pontos', 'href' => route('properties.index')];
            $links[] = ['group' => 'Território', 'label' => 'Endereços', 'href' => route('addresses.index')];
        }
        if ($user?->hasPermission('cities.view')) {
            $links[] = ['group' => 'Território', 'label' => 'Cidades', 'href' => route('cities.index')];
        }
        if ($user?->hasPermission('sectors.view')) {
            $links[] = ['group' => 'Território', 'label' => 'Setores', 'href' => route('sectors.index')];
        }
        if ($user?->hasPermission('communication.view')) {
            $links[] = ['group' => 'Comunicação', 'label' => 'WhatsApp', 'href' => route('communication.messages.index')];
        }
        if ($user?->hasPermission('ai.access')) {
            $links[] = ['group' => 'Inteligência', 'label' => 'Expandor AI', 'href' => route('ai.conversations.index')];
        }
        if ($user?->hasPermission('users.view') || $user?->hasPermission('users.manage')) {
            $links[] = ['group' => 'Equipe', 'label' => 'Central da Equipe', 'href' => route('operations.team')];
        }
        if ($user?->hasPermission('training.view') || $user?->hasPermission('training.manage')) {
            $links[] = ['group' => 'Equipe', 'label' => 'Academia', 'href' => route('training.categories.index')];
        }
        if ($user?->role?->slug === \App\Domains\Company\Models\Role::ADMINISTRATOR && $user?->hasPermission('users.view')) {
            $links[] = ['group' => 'Admin', 'label' => 'Usuários (técnico)', 'href' => route('users.index')];
        }
        if ($user?->hasPermission('sales_app.access')) {
            $links[] = ['group' => 'Campo', 'label' => 'App Campo', 'href' => route('sales-app.dashboard')];
        }
        if ($user?->hasPermission('audit.view')) {
            $links[] = ['group' => 'Admin', 'label' => 'Auditoria', 'href' => route('company.audit.index')];
        }
        if ($user?->hasPermission('privacy.view')) {
            $links[] = ['group' => 'Admin', 'label' => 'Privacidade', 'href' => route('company.privacy.index')];
        }

        return $links;
    }
}
