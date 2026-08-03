<?php

namespace App\Domains\Media\DTOs;

readonly class MediaUploadResult
{
    public function __construct(
        public string $path,
        public ?string $thumbPath = null,
        public string $disk = 'public',
        public string $mime = 'application/octet-stream',
        public bool $optimized = false,
    ) {}

    public function url(): string
    {
        return '/storage/'.ltrim($this->path, '/');
    }

    public function thumbUrl(): ?string
    {
        if ($this->thumbPath === null) {
            return null;
        }

        return '/storage/'.ltrim($this->thumbPath, '/');
    }
}
