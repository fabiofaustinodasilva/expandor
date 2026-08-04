<?php

namespace App\Domains\Media\Enums;

enum MediaCategory: string
{
    case Branding = 'branding';
    case Profiles = 'profiles';
    case Products = 'products';
    case Platform = 'platform';
    case Marketplace = 'marketplace';

    public function label(): string
    {
        return match ($this) {
            self::Branding => 'Branding',
            self::Profiles => 'Perfis',
            self::Products => 'Produtos',
            self::Platform => 'Plataforma',
            self::Marketplace => 'Marketplace',
        };
    }
}
