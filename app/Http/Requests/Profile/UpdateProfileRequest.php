<?php

namespace App\Http\Requests\Profile;

use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'photo' => app(MediaUploadService::class)->rules(MediaPurpose::ProfilePhoto),
            'remove_photo' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return array_merge(
            [
                'name.required' => 'Informe o nome.',
            ],
            app(MediaUploadService::class)->validationMessages('photo', 'foto'),
        );
    }

    protected function prepareForValidation(): void
    {
        app(MediaUploadService::class)->assertRequestFilesValid([
            'photo' => $this->file('photo'),
        ]);
    }
}
