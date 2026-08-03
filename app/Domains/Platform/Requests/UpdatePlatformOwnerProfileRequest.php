<?php

namespace App\Domains\Platform\Requests;

use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePlatformOwnerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->isPlatformAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'photo' => app(MediaUploadService::class)->rules(MediaPurpose::ProfilePhoto),
            'remove_photo' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(
            [
                'name.required' => 'Informe o nome.',
                'email.required' => 'Informe o e-mail.',
                'email.email' => 'Informe um e-mail válido.',
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
