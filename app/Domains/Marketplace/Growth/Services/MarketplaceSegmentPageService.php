<?php

namespace App\Domains\Marketplace\Growth\Services;

use App\Domains\Marketplace\Growth\Models\MarketplaceSegmentPage;
use App\Domains\Marketplace\Growth\Repositories\MarketplaceGrowthContentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MarketplaceSegmentPageService
{
    public function __construct(
        protected MarketplaceGrowthContentRepository $content,
    ) {}

    /** @return Collection<int, MarketplaceSegmentPage> */
    public function all(): Collection
    {
        return $this->content->allSegments();
    }

    public function findBySlug(string $slug): ?MarketplaceSegmentPage
    {
        return $this->content->findSegmentBySlug($slug);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): MarketplaceSegmentPage
    {
        return DB::transaction(function () use ($data, $image) {
            $page = new MarketplaceSegmentPage([
                'slug' => Str::slug((string) ($data['slug'] ?? $data['title'])),
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'description' => $data['description'] ?? null,
                'hero_video' => $data['hero_video'] ?? null,
                'features' => $this->normalizeFeatures($data['features'] ?? null),
                'cta_text' => $data['cta_text'] ?? 'Solicitar demonstração',
                'cta_url' => $data['cta_url'] ?? '#demo',
                'active' => (bool) ($data['active'] ?? true),
            ]);

            if ($image instanceof UploadedFile) {
                $page->hero_image = $image->store('platform/marketplace/segments', 'public');
            }

            $page->save();

            return $page;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MarketplaceSegmentPage $page, array $data, ?UploadedFile $image = null): MarketplaceSegmentPage
    {
        foreach (['title', 'subtitle', 'description', 'hero_video', 'cta_text', 'cta_url'] as $field) {
            if (array_key_exists($field, $data)) {
                $page->{$field} = $data[$field];
            }
        }
        if (isset($data['slug'])) {
            $page->slug = Str::slug((string) $data['slug']);
        }
        if (array_key_exists('features', $data)) {
            $page->features = $this->normalizeFeatures($data['features']);
        }
        if (array_key_exists('active', $data)) {
            $page->active = (bool) $data['active'];
        }
        if ($image instanceof UploadedFile) {
            $page->hero_image = $image->store('platform/marketplace/segments', 'public');
        }
        $page->save();

        return $page;
    }

    public function delete(MarketplaceSegmentPage $page): void
    {
        $page->delete();
    }

    /**
     * @return list<array{title: string, description: string}>|null
     */
    protected function normalizeFeatures(mixed $features): ?array
    {
        if (is_string($features) && $features !== '') {
            $decoded = json_decode($features, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            $rows = [];
            foreach (preg_split('/\r\n|\r|\n/', $features) ?: [] as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                [$t, $d] = array_pad(explode('|', $line, 2), 2, '');
                $rows[] = ['title' => trim($t), 'description' => trim($d)];
            }

            return $rows !== [] ? $rows : null;
        }

        return is_array($features) ? $features : null;
    }
}
