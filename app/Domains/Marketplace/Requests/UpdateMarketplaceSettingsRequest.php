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
            'demo_video_url' => ['nullable', 'string', 'max:500'],
            'conversion_content' => ['nullable', 'array'],
            'conversion_content.social_proof_title' => ['nullable', 'string', 'max:255'],
            'conversion_content.how_it_works' => ['nullable', 'array'],
            'conversion_content.before_after' => ['nullable', 'array'],
            'conversion_content.before_after.before' => ['nullable', 'array'],
            'conversion_content.before_after.after' => ['nullable', 'array'],
            'conversion_content.metrics' => ['nullable', 'array'],
            'conversion_content.benefits' => ['nullable', 'array'],
            'conversion_content.segments' => ['nullable', 'array'],
            'conversion_content.footer' => ['nullable', 'array'],
            'conversion_content.tracking' => ['nullable', 'array'],
            'hero_video' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'max:5120'],
            'favicon' => ['nullable', 'file', 'max:1024'],
            'hero_image' => ['nullable', 'file', 'max:8192'],
            'og_image' => ['nullable', 'file', 'max:8192'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'remove_hero_image' => ['sometimes', 'boolean'],
            'remove_og_image' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $conversion = $this->input('conversion_content', []);
        if (is_array($conversion)) {
            if (array_key_exists('before_text', $conversion) && is_string($conversion['before_text'])) {
                $lines = array_values(array_filter(array_map(
                    'trim',
                    preg_split('/\r\n|\r|\n/', $conversion['before_text']) ?: []
                )));
                if ($lines !== []) {
                    $conversion['before_after']['before'] = $lines;
                }
                unset($conversion['before_text']);
            }
            if (array_key_exists('after_text', $conversion) && is_string($conversion['after_text'])) {
                $lines = array_values(array_filter(array_map(
                    'trim',
                    preg_split('/\r\n|\r|\n/', $conversion['after_text']) ?: []
                )));
                if ($lines !== []) {
                    $conversion['before_after']['after'] = $lines;
                }
                unset($conversion['after_text']);
            }
            if (array_key_exists('how_it_works_text', $conversion) && is_string($conversion['how_it_works_text'])) {
                if (trim($conversion['how_it_works_text']) !== '') {
                    $steps = [];
                    foreach (preg_split('/\r\n|\r|\n/', $conversion['how_it_works_text']) ?: [] as $line) {
                        $line = trim($line);
                        if ($line === '') {
                            continue;
                        }
                        $parts = array_map('trim', explode('|', $line, 2));
                        $steps[] = [
                            'step' => count($steps) + 1,
                            'title' => $parts[0] ?? '',
                            'description' => $parts[1] ?? '',
                        ];
                    }
                    $conversion['how_it_works'] = $steps;
                }
                unset($conversion['how_it_works_text']);
            }
            if (array_key_exists('benefits_text', $conversion) && is_string($conversion['benefits_text'])) {
                if (trim($conversion['benefits_text']) !== '') {
                    $conversion['benefits'] = array_values(array_filter(array_map(
                        'trim',
                        preg_split('/\r\n|\r|\n/', $conversion['benefits_text']) ?: []
                    )));
                }
                unset($conversion['benefits_text']);
            }
            if (array_key_exists('segments_text', $conversion) && is_string($conversion['segments_text'])) {
                if (trim($conversion['segments_text']) !== '') {
                    $segments = [];
                    foreach (preg_split('/\r\n|\r|\n/', $conversion['segments_text']) ?: [] as $line) {
                        $line = trim($line);
                        if ($line === '') {
                            continue;
                        }
                        $parts = array_map('trim', explode('|', $line, 2));
                        $segments[] = [
                            'title' => $parts[0] ?? '',
                            'description' => $parts[1] ?? '',
                        ];
                    }
                    $conversion['segments'] = $segments;
                }
                unset($conversion['segments_text']);
            }
            if (isset($conversion['footer']) && is_array($conversion['footer'])) {
                $conversion['footer'] = array_filter($conversion['footer'], fn ($v) => $v !== null && $v !== '');
            }
            if (isset($conversion['tracking']) && is_array($conversion['tracking'])) {
                $conversion['tracking'] = array_filter($conversion['tracking'], fn ($v) => $v !== null && $v !== '');
            }
            if (isset($conversion['metrics']) && is_array($conversion['metrics'])) {
                $conversion['metrics'] = array_filter(
                    $conversion['metrics'],
                    fn ($value) => $value !== null && $value !== ''
                );
            }
            if (($conversion['social_proof_title'] ?? null) === '') {
                unset($conversion['social_proof_title']);
            }
            $this->merge(['conversion_content' => $conversion]);
        }

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
            'remove_og_image' => $this->boolean('remove_og_image'),
        ]);
    }
}
