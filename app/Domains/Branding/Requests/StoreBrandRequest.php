<?php

namespace App\Domains\Branding\Requests;

use App\Domains\Branding\Models\Brand;
use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Brand::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return BrandValidationRules::rules($this->user()?->company_id);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return BrandValidationRules::messages();
    }

    protected function prepareForValidation(): void
    {
        app(\App\Domains\Media\Services\MediaUploadService::class)->assertRequestFilesValid([
            'logo' => $this->file('logo'),
            'logo_mark' => $this->file('logo_mark'),
            'favicon' => $this->file('favicon'),
            'login_image' => $this->file('login_image'),
        ]);
    }
}
