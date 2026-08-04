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
        'seo_title',
        'seo_description',
        'seo_keywords',
    ];

    protected function casts(): array
    {
        return [
            'whatsapp_enabled' => 'boolean',
            'instagram_enabled' => 'boolean',
            'facebook_enabled' => 'boolean',
            'youtube_enabled' => 'boolean',
            'linkedin_enabled' => 'boolean',
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

    public function whatsappLink(?string $context = null): ?string
    {
        if (! $this->whatsapp_enabled || ! filled($this->whatsapp_number)) {
            return null;
        }

        $number = preg_replace('/\D+/', '', (string) $this->whatsapp_number);
        $base = (string) ($this->whatsapp_message ?: 'Olá, conheci o Expandor pelo site e gostaria de uma demonstração.');
        if (filled($context)) {
            $base = trim($base.' '.$context);
        }
        $text = rawurlencode($base);

        return 'https://wa.me/'.$number.'?text='.$text;
    }
}
