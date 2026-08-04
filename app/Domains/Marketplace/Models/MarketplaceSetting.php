<?php

namespace App\Domains\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MarketplaceSetting extends Model
{
    protected $fillable = [
        'logo',
        'favicon',
        'hero_image',
        'hero_video',
        'demo_video_url',
        'og_image',
        'title',
        'subtitle',
        'description',
        'primary_color',
        'secondary_color',
        'background_color',
        'button_color',
        'whatsapp_enabled',
        'whatsapp_number',
        'whatsapp_message',
        'instagram_enabled',
        'instagram_url',
        'facebook_enabled',
        'facebook_url',
        'youtube_enabled',
        'youtube_url',
        'linkedin_enabled',
        'linkedin_url',
        'tiktok_enabled',
        'tiktok_url',
        'twitter_enabled',
        'twitter_url',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'conversion_content',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_enabled' => 'boolean',
            'instagram_enabled' => 'boolean',
            'facebook_enabled' => 'boolean',
            'youtube_enabled' => 'boolean',
            'linkedin_enabled' => 'boolean',
            'tiktok_enabled' => 'boolean',
            'twitter_enabled' => 'boolean',
            'conversion_content' => 'array',
        ];
    }

    public function mediaUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function hasWhatsAppButton(): bool
    {
        return (bool) $this->whatsapp_enabled && filled($this->whatsapp_number);
    }

    /**
     * Número apenas dígitos, com DDI 55 quando ausente (padrão BR).
     */
    public function whatsappDigits(): ?string
    {
        if (! filled($this->whatsapp_number)) {
            return null;
        }

        $number = preg_replace('/\D+/', '', (string) $this->whatsapp_number) ?: '';
        if ($number === '') {
            return null;
        }

        if (! str_starts_with($number, '55') && strlen($number) <= 11) {
            $number = '55'.$number;
        }

        return $number;
    }

    public function whatsappLink(?string $context = null): ?string
    {
        if (! $this->hasWhatsAppButton()) {
            return null;
        }

        $number = $this->whatsappDigits();
        if ($number === null) {
            return null;
        }

        $base = (string) ($this->whatsapp_message ?: 'Olá! Gostaria de conhecer o Expandor.');
        if (filled($context)) {
            $base = trim($base.' '.$context);
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($base);
    }

    /**
     * Redes sociais ativas com URL válida.
     *
     * @return list<array{key: string, label: string, url: string, event: string|null}>
     */
    public function socialNetworks(): array
    {
        $catalog = [
            ['key' => 'instagram', 'label' => 'Instagram', 'enabled' => 'instagram_enabled', 'url' => 'instagram_url', 'event' => 'marketplace.instagram_clicked'],
            ['key' => 'facebook', 'label' => 'Facebook', 'enabled' => 'facebook_enabled', 'url' => 'facebook_url', 'event' => 'marketplace.facebook_clicked'],
            ['key' => 'linkedin', 'label' => 'LinkedIn', 'enabled' => 'linkedin_enabled', 'url' => 'linkedin_url', 'event' => 'marketplace.linkedin_clicked'],
            ['key' => 'youtube', 'label' => 'YouTube', 'enabled' => 'youtube_enabled', 'url' => 'youtube_url', 'event' => 'marketplace.youtube_clicked'],
            ['key' => 'tiktok', 'label' => 'TikTok', 'enabled' => 'tiktok_enabled', 'url' => 'tiktok_url', 'event' => 'marketplace.tiktok_clicked'],
            ['key' => 'twitter', 'label' => 'X', 'enabled' => 'twitter_enabled', 'url' => 'twitter_url', 'event' => 'marketplace.twitter_clicked'],
        ];

        $links = [];
        foreach ($catalog as $item) {
            $enabled = (bool) $this->{$item['enabled']};
            $url = trim((string) ($this->{$item['url']} ?? ''));
            if (! $enabled || $url === '') {
                continue;
            }

            $links[] = [
                'key' => $item['key'],
                'label' => $item['label'],
                'url' => $url,
                'event' => $item['event'],
            ];
        }

        return $links;
    }

    public function demoVideoEmbedUrl(): ?string
    {
        $url = trim((string) ($this->demo_video_url ?: ''));
        if ($url === '') {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]+)/', $url, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1].'?rel=0&autoplay=1';
        }
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1].'?autoplay=1';
        }
        if (str_ends_with(strtolower(parse_url($url, PHP_URL_PATH) ?: ''), '.mp4') || str_contains($url, '.mp4')) {
            return $url;
        }

        return $url;
    }

    public function demoVideoIsMp4(): bool
    {
        $url = (string) ($this->demo_video_url ?: '');

        return str_contains(strtolower($url), '.mp4');
    }

    /**
     * @return array<string, mixed>
     */
    public function conversionOverrides(): array
    {
        return is_array($this->conversion_content) ? $this->conversion_content : [];
    }
}
