<?php

namespace App\Domains\Marketplace\Services;

use App\Domains\Marketplace\Models\MarketplaceFaq;
use App\Domains\Marketplace\Models\MarketplaceMedia;
use App\Domains\Marketplace\Models\MarketplaceTestimonial;
use App\Domains\Marketplace\Repositories\MarketplaceContentRepository;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MarketplaceMediaService
{
    public function __construct(
        protected MarketplaceContentRepository $content,
        protected MediaUploadService $media,
    ) {}

    /**
     * @return Collection<int, MarketplaceMedia>
     */
    public function all(): Collection
    {
        return MarketplaceMedia::query()->orderBy('order')->orderBy('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $file = null, ?UploadedFile $thumbnail = null): MarketplaceMedia
    {
        return DB::transaction(function () use ($data, $file, $thumbnail) {
            $item = new MarketplaceMedia([
                'type' => $data['type'] ?? 'image',
                'title' => $data['title'] ?? null,
                'caption' => $data['caption'] ?? null,
                'external_url' => $data['external_url'] ?? null,
                'order' => (int) ($data['order'] ?? ((int) MarketplaceMedia::query()->max('order') + 1)),
                'active' => (bool) ($data['active'] ?? true),
            ]);

            if ($file instanceof UploadedFile) {
                $result = $this->media->storeMarketplace($file, MediaPurpose::MarketplaceImage);
                $item->path = $result->path;
                if ($item->type === 'image' && filled($result->thumbPath)) {
                    $item->thumbnail = $result->thumbPath;
                }
            }

            if ($thumbnail instanceof UploadedFile) {
                $thumb = $this->media->storeMarketplace($thumbnail, MediaPurpose::MarketplaceImage);
                $item->thumbnail = $thumb->path;
            }

            $item->save();
            $this->content->forgetPublicCache();

            return $item;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MarketplaceMedia $item, array $data, ?UploadedFile $file = null, ?UploadedFile $thumbnail = null): MarketplaceMedia
    {
        return DB::transaction(function () use ($item, $data, $file, $thumbnail) {
            foreach (['type', 'title', 'caption', 'external_url'] as $field) {
                if (array_key_exists($field, $data)) {
                    $value = $data[$field];
                    $item->{$field} = is_string($value) ? (trim($value) !== '' ? trim($value) : null) : $value;
                }
            }
            if (array_key_exists('order', $data)) {
                $item->order = (int) $data['order'];
            }
            if (array_key_exists('active', $data)) {
                $item->active = (bool) $data['active'];
            }

            if ($file instanceof UploadedFile) {
                $result = $this->media->storeMarketplace($file, MediaPurpose::MarketplaceImage, $item->path, $item->thumbnail);
                $item->path = $result->path;
            }

            if ($thumbnail instanceof UploadedFile) {
                $thumb = $this->media->storeMarketplace($thumbnail, MediaPurpose::MarketplaceImage, $item->thumbnail);
                $item->thumbnail = $thumb->path;
            }

            $item->save();
            $this->content->forgetPublicCache();

            return $item->fresh() ?? $item;
        });
    }

    public function delete(MarketplaceMedia $item): void
    {
        if (filled($item->path)) {
            $this->media->delete($item->path, $item->thumbnail);
        } elseif (filled($item->thumbnail)) {
            $this->media->delete($item->thumbnail);
        }
        $item->delete();
        $this->content->forgetPublicCache();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTestimonial(array $data, ?UploadedFile $avatar = null): MarketplaceTestimonial
    {
        $row = new MarketplaceTestimonial([
            'name' => $data['name'],
            'company' => $data['company'] ?? null,
            'text' => $data['text'],
            'rating' => (int) ($data['rating'] ?? 5),
            'active' => (bool) ($data['active'] ?? true),
            'order' => (int) ($data['order'] ?? ((int) MarketplaceTestimonial::query()->max('order') + 1)),
        ]);

        if ($avatar instanceof UploadedFile) {
            $result = $this->media->storeMarketplace($avatar, MediaPurpose::MarketplaceImage);
            $row->avatar = $result->path;
        }

        $row->save();
        $this->content->forgetPublicCache();

        return $row;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTestimonial(MarketplaceTestimonial $row, array $data, ?UploadedFile $avatar = null): MarketplaceTestimonial
    {
        foreach (['name', 'company', 'text'] as $field) {
            if (array_key_exists($field, $data)) {
                $row->{$field} = $data[$field];
            }
        }
        if (array_key_exists('rating', $data)) {
            $row->rating = (int) $data['rating'];
        }
        if (array_key_exists('active', $data)) {
            $row->active = (bool) $data['active'];
        }
        if (array_key_exists('order', $data)) {
            $row->order = (int) $data['order'];
        }
        if ($avatar instanceof UploadedFile) {
            $result = $this->media->storeMarketplace($avatar, MediaPurpose::MarketplaceImage, $row->avatar);
            $row->avatar = $result->path;
        }
        $row->save();
        $this->content->forgetPublicCache();

        return $row;
    }

    public function deleteTestimonial(MarketplaceTestimonial $row): void
    {
        if (filled($row->avatar)) {
            $this->media->delete($row->avatar);
        }
        $row->delete();
        $this->content->forgetPublicCache();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFaq(array $data): MarketplaceFaq
    {
        $row = MarketplaceFaq::query()->create([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'order' => (int) ($data['order'] ?? ((int) MarketplaceFaq::query()->max('order') + 1)),
            'active' => (bool) ($data['active'] ?? true),
        ]);
        $this->content->forgetPublicCache();

        return $row;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateFaq(MarketplaceFaq $row, array $data): MarketplaceFaq
    {
        foreach (['question', 'answer'] as $field) {
            if (array_key_exists($field, $data)) {
                $row->{$field} = $data[$field];
            }
        }
        if (array_key_exists('order', $data)) {
            $row->order = (int) $data['order'];
        }
        if (array_key_exists('active', $data)) {
            $row->active = (bool) $data['active'];
        }
        $row->save();
        $this->content->forgetPublicCache();

        return $row;
    }

    public function deleteFaq(MarketplaceFaq $row): void
    {
        $row->delete();
        $this->content->forgetPublicCache();
    }
}
