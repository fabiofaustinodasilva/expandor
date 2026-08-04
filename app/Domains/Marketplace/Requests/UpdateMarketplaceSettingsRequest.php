<?php

namespace App\Domains\Marketplace\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMarketplaceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('marketplace.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'button_color' => ['nullable', 'string', 'max:20'],
            'whatsapp_enabled' => ['sometimes', 'boolean'],
            'whatsapp_number' => ['nullable', 'string', 'max:40'],
            'whatsapp_message' => ['nullable', 'string', 'max:255'],
            'instagram_enabled' => ['sometimes', 'boolean'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'facebook_enabled' => ['sometimes', 'boolean'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'youtube_enabled' => ['sometimes', 'boolean'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'linkedin_enabled' => ['sometimes', 'boolean'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'tiktok_enabled' => ['sometimes', 'boolean'],
            'tiktok_url' => ['nullable', 'url', 'max:255'],
            'twitter_enabled' => ['sometimes', 'boolean'],
            'twitter_url' => ['nullable', 'url', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'hero_video' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'max:5120'],
            'favicon' => ['nullable', 'file', 'max:1024'],
            'hero_image' => ['nullable', 'file', 'max:8192'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'remove_hero_image' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'whatsapp_enabled' => $this->boolean('whatsapp_enabled'),
            'instagram_enabled' => $this->boolean('instagram_enabled'),
            'facebook_enabled' => $this->boolean('facebook_enabled'),
            'youtube_enabled' => $this->boolean('youtube_enabled'),
            'linkedin_enabled' => $this->boolean('linkedin_enabled'),
            'tiktok_enabled' => $this->boolean('tiktok_enabled'),
            'twitter_enabled' => $this->boolean('twitter_enabled'),
            'remove_logo' => $this->boolean('remove_logo'),
            'remove_favicon' => $this->boolean('remove_favicon'),
            'remove_hero_image' => $this->boolean('remove_hero_image'),
        ]);
    }
}
