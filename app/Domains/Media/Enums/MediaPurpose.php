<?php

namespace App\Domains\Media\Enums;

enum MediaPurpose: string
{
    case Logo = 'logo';
    case LogoMark = 'logo_mark';
    case Favicon = 'favicon';
    case LoginImage = 'login_image';
    case ProfilePhoto = 'photo';
    case ProductImage = 'product_image';
    case MarketplaceImage = 'marketplace_image';

    public function allowsSvg(): bool
    {
        return in_array($this, [self::Logo, self::LogoMark], true);
    }

    public function allowsIco(): bool
    {
        return $this === self::Favicon;
    }

    public function shouldOptimize(): bool
    {
        return ! in_array($this, [self::Favicon], true);
    }

    public function shouldThumbnail(): bool
    {
        return in_array($this, [
            self::ProfilePhoto,
            self::ProductImage,
            self::Logo,
            self::MarketplaceImage,
        ], true);
    }

    public function thumbnailMaxEdge(): int
    {
        return match ($this) {
            self::ProfilePhoto => 256,
            self::ProductImage => 320,
            self::Logo => 480,
            self::MarketplaceImage => 960,
            default => 256,
        };
    }
}
