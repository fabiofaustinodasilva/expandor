@props([
    'name',
    'label',
    'currentUrl' => null,
    'accept' => 'image/jpeg,image/png,image/webp,image/svg+xml',
    'hint' => 'JPG, PNG ou WEBP até 5MB.',
    'removeName' => null,
    'previewHeight' => '72px',
])
@php
    $inputId = $attributes->get('id') ?: $name;
    $previewId = $inputId.'_preview';
    $removeId = $inputId.'_remove';
@endphp
<div class="form-group media-upload" data-media-upload>
    <label for="{{ $inputId }}">{{ $label }}</label>
    <div style="display:flex; gap:.85rem; align-items:flex-start; flex-wrap:wrap;">
        <div id="{{ $previewId }}"
             data-media-preview
             style="width:{{ $previewHeight }}; height:{{ $previewHeight }}; border-radius:.85rem; border:1px solid var(--border); background:var(--bg-soft); display:grid; place-items:center; overflow:hidden; flex:0 0 auto;">
            @if($currentUrl)
                <img src="{{ $currentUrl }}" alt="Preview" style="width:100%; height:100%; object-fit:contain;">
            @else
                <span class="header-meta" style="font-size:.7rem; padding:.35rem; text-align:center;">Sem imagem</span>
            @endif
        </div>
        <div style="flex:1; min-width:180px;">
            <input class="form-control" id="{{ $inputId }}" name="{{ $name }}" type="file" accept="{{ $accept }}" data-media-input>
            <p class="header-meta" style="margin:.4rem 0 0;">{{ $hint }}</p>
            @error($name)<div class="header-meta" style="color:var(--highlight);margin-top:.35rem;">{{ $message }}</div>@enderror
            @if($removeName && $currentUrl)
                <label style="display:inline-flex; gap:.4rem; align-items:center; margin-top:.55rem; color:var(--muted); font-size:.85rem;">
                    <input type="checkbox" id="{{ $removeId }}" name="{{ $removeName }}" value="1" data-media-remove>
                    Remover imagem atual
                </label>
            @endif
        </div>
    </div>
</div>
@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-media-upload]').forEach((root) => {
                const input = root.querySelector('[data-media-input]');
                const preview = root.querySelector('[data-media-preview]');
                const remove = root.querySelector('[data-media-remove]');
                if (!input || !preview) return;

                input.addEventListener('change', () => {
                    const file = input.files && input.files[0];
                    if (!file) return;
                    if (remove) remove.checked = false;
                    const url = URL.createObjectURL(file);
                    preview.innerHTML = '<img src="'+url+'" alt="Preview" style="width:100%;height:100%;object-fit:contain;">';
                });

                if (remove) {
                    remove.addEventListener('change', () => {
                        if (remove.checked) {
                            input.value = '';
                            preview.innerHTML = '<span class="header-meta" style="font-size:.7rem;padding:.35rem;text-align:center;">Será removida</span>';
                        }
                    });
                }
            });
        </script>
    @endpush
@endonce
