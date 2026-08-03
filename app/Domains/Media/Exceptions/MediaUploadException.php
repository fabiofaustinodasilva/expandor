<?php

namespace App\Domains\Media\Exceptions;

use Illuminate\Validation\ValidationException;

class MediaUploadException
{
    public static function invalid(string $message, string $field = 'file'): never
    {
        throw ValidationException::withMessages([
            $field => [$message],
        ]);
    }
}
