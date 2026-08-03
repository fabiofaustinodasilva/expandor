<?php

namespace App\Domains\Branding\Requests;

use App\Domains\Branding\Enums\BrandTheme;
use App\Domains\Branding\Models\Brand;
use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use Illuminate\Validation\Rule;

final class BrandValidationRules
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(?int $companyId = null): array
    {
        /** @var MediaUploadService $media */
        $media = app(MediaUploadService::class);

        return [
            'system_name' => ['required', 'string', 'max:120'],
            'display_name' => ['required', 'string', 'max:120'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'logo' => $media->rules(MediaPurpose::Logo),
            'logo_mark' => $media->rules(MediaPurpose::LogoMark),
            'favicon' => $media->rules(MediaPurpose::Favicon),
            'login_image' => $media->rules(MediaPurpose::LoginImage),
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_logo_mark' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'remove_login_image' => ['sometimes', 'boolean'],
            'theme' => ['required', Rule::enum(BrandTheme::class)],
            'colors' => ['nullable', 'array'],
            'colors.bg' => ['nullable', 'string', 'max:20'],
            'colors.bg_elevated' => ['nullable', 'string', 'max:20'],
            'colors.bg_soft' => ['nullable', 'string', 'max:20'],
            'colors.border' => ['nullable', 'string', 'max:20'],
            'colors.text' => ['nullable', 'string', 'max:20'],
            'colors.muted' => ['nullable', 'string', 'max:20'],
            'colors.accent' => ['nullable', 'string', 'max:20'],
            'colors.accent_2' => ['nullable', 'string', 'max:20'],
            'colors.primary' => ['nullable', 'string', 'max:20'],
            'colors.secondary' => ['nullable', 'string', 'max:20'],
            'colors.highlight' => ['nullable', 'string', 'max:20'],
            'colors.success' => ['nullable', 'string', 'max:20'],
            'colors.warning' => ['nullable', 'string', 'max:20'],
            'colors.primary_color' => ['nullable', 'string', 'max:20'],
            'colors.secondary_color' => ['nullable', 'string', 'max:20'],
            'colors.highlight_color' => ['nullable', 'string', 'max:20'],
            'fonts' => ['nullable', 'array'],
            'fonts.family' => ['nullable', 'string', 'max:180'],
            'fonts.heading' => ['nullable', 'string', 'max:180'],
            'support_email' => ['nullable', 'email', 'max:190'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'socials' => ['nullable', 'array'],
            'socials.facebook' => ['nullable', 'string', 'max:255'],
            'socials.instagram' => ['nullable', 'string', 'max:255'],
            'socials.linkedin' => ['nullable', 'string', 'max:255'],
            'socials.twitter' => ['nullable', 'string', 'max:255'],
            'socials.youtube' => ['nullable', 'string', 'max:255'],
            'socials.whatsapp' => ['nullable', 'string', 'max:255'],
            'custom_domain' => [
                'nullable',
                'string',
                'max:190',
                'regex:/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/i',
                Rule::unique('brands', 'custom_domain')->ignore(
                    Brand::query()->withoutGlobalScopes()->where('company_id', $companyId)->value('id')
                ),
            ],
            'custom_css' => ['nullable', 'string', 'max:20000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        /** @var MediaUploadService $media */
        $media = app(MediaUploadService::class);

        return array_merge(
            [
                'system_name.required' => 'Informe o nome do sistema.',
                'display_name.required' => 'Informe o nome de exibição.',
                'custom_domain.regex' => 'Domínio inválido. Use apenas o host (ex.: crm.suaempresa.com).',
                'custom_domain.unique' => 'Este domínio personalizado já está em uso.',
            ],
            $media->validationMessages('logo', 'logo'),
            $media->validationMessages('logo_mark', 'logo reduzida'),
            $media->validationMessages('favicon', 'favicon'),
            $media->validationMessages('login_image', 'imagem de login'),
        );
    }
}
