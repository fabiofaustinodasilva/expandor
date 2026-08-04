<?php

namespace App\Domains\Marketplace\Enums;

enum MarketplaceSectionType: string
{
    case Hero = 'hero';
    case Showcase = 'showcase';
    case HowItWorks = 'how_it_works';
    case BeforeAfter = 'before_after';
    case About = 'about';
    case Features = 'features';
    case SocialProof = 'social_proof';
    case Video = 'video';
    case Gallery = 'gallery';
    case Testimonials = 'testimonials';
    case Faq = 'faq';
    case Plans = 'plans';
    case Cta = 'cta';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::Showcase => 'Demonstração visual',
            self::HowItWorks => 'Como funciona',
            self::BeforeAfter => 'Antes e depois',
            self::About => 'Quem Somos',
            self::Features => 'Recursos',
            self::SocialProof => 'Prova social',
            self::Video => 'Vídeo',
            self::Gallery => 'Galeria',
            self::Testimonials => 'Depoimentos',
            self::Faq => 'FAQ',
            self::Plans => 'Planos',
            self::Cta => 'CTA',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
