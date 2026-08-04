<?php

namespace App\Domains\Marketplace\Services;

use App\Domains\Marketplace\Enums\MarketplaceSectionType;
use App\Domains\Marketplace\Models\MarketplaceSection;
use App\Domains\Marketplace\Repositories\MarketplaceContentRepository;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketplaceSectionService
{
    public function __construct(
        protected MarketplaceContentRepository $content,
        protected MediaUploadService $media,
    ) {}

    /**
     * @return Collection<int, MarketplaceSection>
     */
    public function all(): Collection
    {
        return $this->content->allSections();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): MarketplaceSection
    {
        return DB::transaction(function () use ($data, $image) {
            $section = new MarketplaceSection([
                'type' => $data['type'] instanceof MarketplaceSectionType
                    ? $data['type']
                    : MarketplaceSectionType::from((string) $data['type']),
                'title' => $data['title'] ?? null,
                'subtitle' => $data['subtitle'] ?? null,
                'description' => $data['description'] ?? null,
                'video' => $data['video'] ?? null,
                'button_text' => $data['button_text'] ?? null,
                'button_url' => $data['button_url'] ?? null,
                'order' => (int) ($data['order'] ?? $this->content->nextSectionOrder()),
                'active' => (bool) ($data['active'] ?? true),
            ]);

            if ($image instanceof UploadedFile) {
                $result = $this->media->storeMarketplace($image, MediaPurpose::MarketplaceImage);
                $section->image = $result->path;
            }

            $section->save();
            $this->content->forgetPublicCache();

            return $section;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MarketplaceSection $section, array $data, ?UploadedFile $image = null, bool $removeImage = false): MarketplaceSection
    {
        return DB::transaction(function () use ($section, $data, $image, $removeImage) {
            if (isset($data['type'])) {
                $section->type = $data['type'] instanceof MarketplaceSectionType
                    ? $data['type']
                    : MarketplaceSectionType::from((string) $data['type']);
            }

            foreach (['title', 'subtitle', 'description', 'video', 'button_text', 'button_url'] as $field) {
                if (array_key_exists($field, $data)) {
                    $value = $data[$field];
                    $section->{$field} = is_string($value) ? (trim($value) !== '' ? trim($value) : null) : $value;
                }
            }

            if (array_key_exists('order', $data)) {
                $section->order = (int) $data['order'];
            }
            if (array_key_exists('active', $data)) {
                $section->active = (bool) $data['active'];
            }

            if ($removeImage && filled($section->image)) {
                $this->media->delete($section->image);
                $section->image = null;
            }

            if ($image instanceof UploadedFile) {
                $result = $this->media->storeMarketplace($image, MediaPurpose::MarketplaceImage, $section->image);
                $section->image = $result->path;
            }

            $section->save();
            $this->content->forgetPublicCache();

            return $section->fresh() ?? $section;
        });
    }

    public function toggleActive(MarketplaceSection $section): MarketplaceSection
    {
        $section->active = ! $section->active;
        $section->save();
        $this->content->forgetPublicCache();

        return $section;
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach (array_values($orderedIds) as $index => $id) {
                MarketplaceSection::query()
                    ->where('id', (int) $id)
                    ->update(['order' => $index + 1]);
            }
            $this->content->forgetPublicCache();
        });
    }

    public function delete(MarketplaceSection $section): void
    {
        if (filled($section->image)) {
            $this->media->delete($section->image);
        }
        $section->delete();
        $this->content->forgetPublicCache();
    }
}
