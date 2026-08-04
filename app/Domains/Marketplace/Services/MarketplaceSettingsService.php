<?php

namespace App\Domains\Marketplace\Services;

use App\Domains\Marketplace\Models\MarketplaceSetting;
use App\Domains\Marketplace\Repositories\MarketplaceContentRepository;
use App\Domains\Marketplace\Repositories\MarketplaceSettingsRepository;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MarketplaceSettingsService
{
    public function __construct(
        protected MarketplaceSettingsRepository $settings,
        protected MarketplaceContentRepository $content,
        protected MediaUploadService $media,
    ) {}

    public function current(): MarketplaceSetting
    {
        return $this->settings->current();
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $files
     * @param  array<string, bool>  $removals
     */
    public function update(array $data, array $files = [], array $removals = []): MarketplaceSetting
    {
        return DB::transaction(function () use ($data, $files, $removals) {
            $row = $this->settings->current();

            foreach ([
                'title', 'subtitle', 'description',
                'primary_color', 'secondary_color', 'background_color', 'button_color',
                'whatsapp_number', 'whatsapp_message',
                'instagram_url', 'facebook_url', 'youtube_url', 'linkedin_url',
                'tiktok_url', 'twitter_url',
                'seo_title', 'seo_description', 'seo_keywords',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $value = $data[$field];
                    $row->{$field} = is_string($value) ? (trim($value) !== '' ? trim($value) : null) : $value;
                }
            }

            foreach ([
                'whatsapp_enabled', 'instagram_enabled', 'facebook_enabled',
                'youtube_enabled', 'linkedin_enabled', 'tiktok_enabled', 'twitter_enabled',
            ] as $flag) {
                // prepareForValidation sempre envia os booleans; fallback false se ausente.
                $row->{$flag} = array_key_exists($flag, $data) ? (bool) $data[$flag] : false;
            }

            $fileMap = [
                'logo' => MediaPurpose::Logo,
                'favicon' => MediaPurpose::Favicon,
                'hero_image' => MediaPurpose::MarketplaceImage,
            ];

            foreach ($fileMap as $field => $purpose) {
                if (! empty($removals[$field])) {
                    if (filled($row->{$field})) {
                        $this->media->delete($row->{$field});
                    }
                    $row->{$field} = null;
                }

                $file = $files[$field] ?? null;
                if ($file instanceof UploadedFile) {
                    $result = $this->media->storeMarketplace($file, $purpose, $row->{$field});
                    $row->{$field} = $result->path;
                }
            }

            if (array_key_exists('hero_video', $data)) {
                $video = trim((string) ($data['hero_video'] ?? ''));
                $row->hero_video = $video !== '' ? $video : null;
            }

            $saved = $this->settings->save($row);
            $this->content->forgetPublicCache();

            return $saved;
        });
    }
}
