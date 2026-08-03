<?php

namespace App\Http\Controllers\Web\Platform;

use App\Domains\Media\Enums\MediaPurpose;
use App\Domains\Media\Services\MediaUploadService;
use App\Domains\Platform\Services\PlatformBrandingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformBrandingController extends Controller
{
    public function __construct(
        protected PlatformBrandingService $branding,
        protected MediaUploadService $media,
    ) {}

    public function edit(): View
    {
        $this->authorize('platform.manageBranding');

        $row = $this->branding->current();
        $payload = $this->branding->payload();

        return view('platform.branding.edit', [
            'row' => $row,
            'payload' => $payload,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('platform.manageBranding');

        $files = [
            'logo' => $request->file('logo'),
            'logo_small' => $request->file('logo_small'),
            'favicon' => $request->file('favicon'),
        ];

        // Diagnóstico antes do Validator (UPLOAD_ERR_* → mensagem amigável, não validation.uploaded).
        $this->media->assertRequestFilesValid($files);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'highlight_color' => ['nullable', 'string', 'max:20'],
            'logo' => $this->media->rules(MediaPurpose::Logo),
            'logo_small' => $this->media->rules(MediaPurpose::LogoMark),
            'favicon' => $this->media->rules(MediaPurpose::Favicon),
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_logo_small' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
        ], array_merge(
            [
                'name.required' => 'Informe o nome da plataforma.',
            ],
            $this->media->validationMessages('logo', 'logo'),
            $this->media->validationMessages('logo_small', 'logo reduzida'),
            $this->media->validationMessages('favicon', 'favicon'),
        ));

        $this->branding->update(
            $validated,
            $files,
            [
                'logo' => $request->boolean('remove_logo'),
                'logo_small' => $request->boolean('remove_logo_small'),
                'favicon' => $request->boolean('remove_favicon'),
            ],
            $request->user(),
        );

        return redirect()
            ->route('platform.branding.edit')
            ->with('success', 'Identidade da plataforma atualizada.'.(
                $request->hasFile('logo') || $request->hasFile('logo_small') || $request->hasFile('favicon')
                    ? ' Upload concluído — a tela de login já usa esta marca.'
                    : (
                        $request->boolean('remove_logo') || $request->boolean('remove_logo_small') || $request->boolean('remove_favicon')
                            ? ' Remoção concluída.'
                            : ' A tela de login já usa esta marca.'
                    )
            ));
    }
}
