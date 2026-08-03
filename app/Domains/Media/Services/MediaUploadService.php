<?php

namespace App\Domains\Media\Services;

use App\Domains\Media\DTOs\MediaUploadResult;
use App\Domains\Media\Enums\MediaCategory;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Exceptions\MediaUploadException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Upload SaaS centralizado (multi-tenant + plataforma).
 *
 * Tenant: companies/{company_id}/{branding|profiles|products}/...
 * Platform: platform/branding/...
 */
class MediaUploadService
{
    public const DISK = 'public';

    public const MAX_KILOBYTES = 5120; // 5 MB

    /** @var list<string> */
    protected const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'bat', 'cmd', 'com',
        'msi', 'scr', 'js', 'jar', 'sh', 'cgi', 'pl', 'py', 'rb', 'dll', 'so',
    ];

    /** @var list<string> */
    protected const BLOCKED_MIMES = [
        'application/x-php',
        'application/x-httpd-php',
        'application/x-msdownload',
        'application/x-executable',
        'application/x-sh',
        'text/x-php',
        'application/javascript',
        'text/javascript',
        'application/octet-stream',
    ];

    public function store(
        UploadedFile $file,
        int $companyId,
        MediaCategory $category,
        MediaPurpose $purpose,
        ?string $replacePath = null,
        ?string $replaceThumbPath = null,
        string $field = 'file',
    ): MediaUploadResult {
        return $this->persistToDirectory(
            $file,
            sprintf('companies/%d/%s', $companyId, $category->value),
            $purpose,
            $replacePath,
            $replaceThumbPath,
            $field,
            ['company_id' => $companyId],
        );
    }

    /**
     * Branding global Expandor — path: platform/branding/...
     */
    public function storePlatform(
        UploadedFile $file,
        MediaPurpose $purpose,
        ?string $replacePath = null,
        ?string $replaceThumbPath = null,
        string $field = 'file',
    ): MediaUploadResult {
        return $this->persistToDirectory(
            $file,
            'platform/branding',
            $purpose,
            $replacePath,
            $replaceThumbPath,
            $field,
            ['scope' => 'platform'],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function persistToDirectory(
        UploadedFile $file,
        string $directory,
        MediaPurpose $purpose,
        ?string $replacePath,
        ?string $replaceThumbPath,
        string $field,
        array $context = [],
    ): MediaUploadResult {
        $this->assertValidUpload($file, $purpose, $field);

        $mime = $this->detectMime($file);
        $extension = $this->resolvedExtension($file, $mime, $purpose);
        $basename = Str::uuid()->toString();
        $relativePath = "{$directory}/{$basename}.{$extension}";

        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '') {
            MediaUploadException::invalid('Não foi possível ler o arquivo enviado.', $field);
        }

        $optimized = false;
        $thumbPath = null;
        $originalBinary = $binary;
        $originalMime = $mime;
        $originalPath = $relativePath;

        if ($this->canRasterOptimize($mime, $purpose)) {
            try {
                $webp = $this->encodeWebp($binary, $mime);
                if ($webp !== null) {
                    $relativePath = "{$directory}/{$basename}.webp";
                    $binary = $webp;
                    $mime = 'image/webp';
                    $optimized = true;
                }
            } catch (\Throwable $e) {
                Log::warning('media.optimize_failed', array_merge($context, [
                    'purpose' => $purpose->value,
                    'message' => $e->getMessage(),
                ]));
                $relativePath = $originalPath;
                $binary = $originalBinary;
                $mime = $originalMime;
                $optimized = false;
            }

            try {
                if ($purpose->shouldThumbnail()) {
                    $thumbSource = $optimized ? $binary : $originalBinary;
                    $thumbMime = $optimized ? 'image/webp' : $originalMime;
                    $thumbBinary = $this->encodeThumbnail($thumbSource, $thumbMime, $purpose->thumbnailMaxEdge());
                    if ($thumbBinary !== null) {
                        $thumbPath = "{$directory}/thumbs/{$basename}.webp";
                        $this->write($thumbPath, $thumbBinary, $field);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('media.thumbnail_failed', array_merge($context, [
                    'purpose' => $purpose->value,
                    'message' => $e->getMessage(),
                ]));
                $thumbPath = null;
            }
        }

        $this->write($relativePath, $binary, $field);

        if (! Storage::disk(self::DISK)->exists($relativePath)) {
            MediaUploadException::invalid('Erro ao salvar imagem no storage.', $field);
        }

        $oldThumb = $this->normalizeStoragePath($replaceThumbPath)
            ?? ($replacePath ? $this->siblingThumbPath((string) $this->normalizeStoragePath($replacePath)) : null);
        $this->deletePair($this->normalizeStoragePath($replacePath), $oldThumb);

        return new MediaUploadResult(
            path: $relativePath,
            thumbPath: $thumbPath,
            disk: self::DISK,
            mime: $mime,
            optimized: $optimized,
        );
    }

    public function delete(?string $path, ?string $thumbPath = null): void
    {
        $path = $this->normalizeStoragePath($path);
        $thumbPath = $this->normalizeStoragePath($thumbPath) ?? ($path ? $this->siblingThumbPath($path) : null);
        $this->deletePair($path, $thumbPath);
    }

    public function url(?string $path): ?string
    {
        $path = $this->normalizeStoragePath($path);

        if ($path === null || ! $this->isAllowedStoragePath($path)) {
            return null;
        }

        // Evita <img>/favicon 404 quando o arquivo sumiu do disco.
        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return '/storage/'.$path;
    }

    public function exists(?string $path): bool
    {
        $path = $this->normalizeStoragePath($path);

        return $path !== null
            && $this->isAllowedStoragePath($path)
            && Storage::disk(self::DISK)->exists($path);
    }

    /**
     * Regras Laravel leves — a validação MIME real fica no serviço.
     *
     * @return list<mixed>
     */
    public function rules(MediaPurpose $purpose, bool $required = false): array
    {
        $mimes = ['jpg', 'jpeg', 'png', 'webp'];
        if ($purpose->allowsSvg()) {
            $mimes[] = 'svg';
        }
        if ($purpose->allowsIco()) {
            $mimes[] = 'ico';
        }

        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            'file',
            'max:'.self::MAX_KILOBYTES,
            'mimes:'.implode(',', $mimes),
        ]));
    }

    public function assertValidUpload(UploadedFile $file, MediaPurpose $purpose, string $field = 'file'): void
    {
        $this->logUploadAttempt($file, $field);

        if (! $file->isValid()) {
            MediaUploadException::invalid($this->friendlyUploadError($file), $field);
        }

        if ($file->getSize() > self::MAX_KILOBYTES * 1024) {
            MediaUploadException::invalid('A imagem excede 5MB. Envie um arquivo menor.', $field);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            MediaUploadException::invalid('Tipo de arquivo bloqueado por segurança.', $field);
        }

        $mime = $this->detectMime($file);

        // octet-stream só é bloqueado se a extensão também for suspeita.
        if ($mime === 'application/octet-stream' && ! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'ico'], true)) {
            MediaUploadException::invalid('MIME inválido. Use PNG, JPG, JPEG ou WEBP.', $field);
        }

        if (in_array($mime, array_diff(self::BLOCKED_MIMES, ['application/octet-stream']), true)) {
            MediaUploadException::invalid('MIME inválido. Use PNG, JPG, JPEG ou WEBP.', $field);
        }

        $allowed = $this->allowedMimes($purpose);
        if (! in_array($mime, $allowed, true) && ! ($mime === 'application/octet-stream' && in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'ico'], true))) {
            MediaUploadException::invalid(
                'Formato não permitido. Use PNG, JPG, JPEG ou WEBP'
                .($purpose->allowsSvg() ? ' (SVG permitido neste campo)' : '')
                .($purpose->allowsIco() ? ' (ICO permitido neste campo)' : '').'.',
                $field
            );
        }

        if ($mime === 'image/svg+xml' || $extension === 'svg') {
            $contents = (string) @file_get_contents($file->getRealPath());
            if ($contents === '' || preg_match('/<script|onload=|onerror=|javascript:/i', $contents) === 1) {
                MediaUploadException::invalid(
                    $contents === ''
                        ? 'Arquivo corrompido ou vazio. Envie a imagem novamente.'
                        : 'SVG rejeitado: conteúdo inseguro detectado.',
                    $field
                );
            }
        }
    }

    /**
     * Mensagens amigáveis para regras Laravel de arquivo (evita chave crua validation.uploaded).
     *
     * @return array<string, string>
     */
    public function validationMessages(string $field, string $label = 'imagem'): array
    {
        $generic = 'Não foi possível enviar a imagem. Verifique tamanho e formato.';

        return [
            "{$field}.uploaded" => $generic.' (upload bloqueado pelo PHP — limite do servidor ou arquivo corrompido).',
            "{$field}.file" => $generic,
            "{$field}.max" => 'A '.$label.' deve ter no máximo 5MB.',
            "{$field}.mimes" => 'Formato inválido. Use PNG, JPG, JPEG ou WEBP.',
            "{$field}.mimetypes" => 'MIME inválido. Use PNG, JPG, JPEG ou WEBP.',
        ];
    }

    public function friendlyUploadError(UploadedFile $file): string
    {
        $generic = 'Não foi possível enviar a imagem. Verifique tamanho e formato.';

        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Arquivo maior que o limite do servidor (máx. 5MB na aplicação). '.$generic,
            UPLOAD_ERR_PARTIAL => 'Upload incompleto (arquivo corrompido na transferência). Tente novamente.',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado.',
            UPLOAD_ERR_NO_TMP_DIR => 'Upload bloqueado pelo PHP: pasta temporária ausente.',
            UPLOAD_ERR_CANT_WRITE => 'Upload bloqueado pelo PHP: falha ao gravar no disco.',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado pelo PHP: extensão do servidor interrompeu o envio.',
            default => $generic,
        };
    }

    public function logUploadAttempt(UploadedFile $file, string $field = 'file'): void
    {
        $size = null;
        try {
            $size = $file->getSize();
        } catch (\Throwable) {
            $size = null;
        }

        Log::info('media.upload_attempt', [
            'field' => $field,
            'original_name' => $file->getClientOriginalName(),
            'size' => $size,
            'client_mime' => $file->getClientMimeType(),
            'error' => $file->getError(),
            'error_message' => $file->getErrorMessage(),
            'is_valid' => $file->isValid(),
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
        ]);
    }

    /**
     * Pré-valida arquivos do request antes do Validator Laravel.
     *
     * @param  array<string, UploadedFile|null>  $files
     */
    public function assertRequestFilesValid(array $files): void
    {
        foreach ($files as $field => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $this->logUploadAttempt($file, $field);

            if (! $file->isValid()) {
                MediaUploadException::invalid($this->friendlyUploadError($file), $field);
            }
        }
    }

    /**
     * @return list<string>
     */
    protected function allowedMimes(MediaPurpose $purpose): array
    {
        $mimes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/jpg',
            'image/pjpeg',
        ];

        if ($purpose->allowsSvg()) {
            $mimes[] = 'image/svg+xml';
        }

        if ($purpose->allowsIco()) {
            $mimes[] = 'image/x-icon';
            $mimes[] = 'image/vnd.microsoft.icon';
            $mimes[] = 'image/ico';
        }

        return $mimes;
    }

    protected function detectMime(UploadedFile $file): string
    {
        $mime = null;

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detected = finfo_file($finfo, $file->getRealPath());
                finfo_close($finfo);
                if (is_string($detected) && $detected !== '') {
                    $mime = strtolower($detected);
                }
            }
        }

        $mime ??= strtolower((string) ($file->getMimeType() ?: 'application/octet-stream'));

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($mime, ['text/xml', 'application/xml', 'text/plain', 'text/html'], true) && $ext === 'svg') {
            return 'image/svg+xml';
        }

        if (in_array($mime, ['image/jpg', 'image/pjpeg'], true)) {
            return 'image/jpeg';
        }

        // Fallback por extensão quando o SO reporta octet-stream.
        if ($mime === 'application/octet-stream') {
            return match ($ext) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'ico' => 'image/x-icon',
                default => $mime,
            };
        }

        return $mime;
    }

    protected function resolvedExtension(UploadedFile $file, string $mime, MediaPurpose $purpose): string
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg', 'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/x-icon', 'image/vnd.microsoft.icon', 'image/ico' => 'ico',
            default => strtolower((string) $file->getClientOriginalExtension()) ?: 'bin',
        };
    }

    protected function canRasterOptimize(string $mime, MediaPurpose $purpose): bool
    {
        if (! $purpose->shouldOptimize()) {
            return false;
        }

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            return false;
        }

        return in_array($mime, ['image/jpeg', 'image/jpg', 'image/pjpeg', 'image/png', 'image/webp'], true);
    }

    protected function encodeWebp(string $binary, string $sourceMime): ?string
    {
        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return null;
        }

        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($image);
        }
        if (function_exists('imagealphablending') && function_exists('imagesavealpha')) {
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }

        ob_start();
        $ok = imagewebp($image, null, 82);
        imagedestroy($image);
        $out = ob_get_clean();

        if (! $ok || ! is_string($out) || $out === '') {
            return null;
        }

        return $out;
    }

    protected function encodeThumbnail(string $binary, string $mime, int $maxEdge): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width < 1 || $height < 1) {
            imagedestroy($image);

            return null;
        }

        $scale = min(1, $maxEdge / max($width, $height));
        $targetW = max(1, (int) round($width * $scale));
        $targetH = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($targetW, $targetH);
        if ($thumb === false) {
            imagedestroy($image);

            return null;
        }

        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefilledrectangle($thumb, 0, 0, $targetW, $targetH, $transparent);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $targetW, $targetH, $width, $height);

        ob_start();
        $ok = function_exists('imagewebp') ? imagewebp($thumb, null, 80) : imagejpeg($thumb, null, 82);
        imagedestroy($image);
        imagedestroy($thumb);
        $out = ob_get_clean();

        if (! $ok || ! is_string($out) || $out === '') {
            return null;
        }

        return $out;
    }

    protected function write(string $path, string $binary, string $field): void
    {
        try {
            $wrote = Storage::disk(self::DISK)->put($path, $binary);
        } catch (\Throwable $e) {
            Log::error('media.write_failed', [
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
            MediaUploadException::invalid('Erro ao salvar imagem. Verifique permissões do storage.', $field);
        }

        if ($wrote !== true) {
            MediaUploadException::invalid('Erro ao salvar imagem no storage.', $field);
        }
    }

    protected function deletePair(?string $path, ?string $thumbPath = null): void
    {
        foreach ([$path, $thumbPath] as $item) {
            $item = $this->normalizeStoragePath($item);

            if ($item === null || ! $this->isAllowedStoragePath($item)) {
                continue;
            }

            try {
                Storage::disk(self::DISK)->delete($item);
            } catch (\Throwable) {
                // ignore delete failures
            }
        }
    }

    protected function normalizeStoragePath(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }

        return $path;
    }

    protected function isAllowedStoragePath(string $path): bool
    {
        return str_starts_with($path, 'companies/')
            || str_starts_with($path, 'platform/')
            || str_starts_with($path, 'brands/')
            || str_starts_with($path, 'users/');
    }

    protected function siblingThumbPath(string $path): ?string
    {
        $path = $this->normalizeStoragePath($path);

        if ($path === null || str_contains($path, '/thumbs/')) {
            return null;
        }

        $directory = dirname($path);
        $basename = pathinfo($path, PATHINFO_FILENAME);

        if ($directory === '.' || $basename === '') {
            return null;
        }

        return $directory.'/thumbs/'.$basename.'.webp';
    }
}
