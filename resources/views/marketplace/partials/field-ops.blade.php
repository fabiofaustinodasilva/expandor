@php $fieldOps = $premiumData['field_ops'] ?? []; @endphp
@if(!empty($fieldOps))
    <div class="mkp-field-ops" data-mkp-field-ops>
        <div class="mkp-field-ops-tabs" role="tablist" aria-label="Telas do EXP Vendedor">
            @foreach($fieldOps as $index => $screen)
                <button type="button"
                        class="mkp-field-ops-tab {{ $index === 0 ? 'is-active' : '' }}"
                        role="tab"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                        data-mkp-field-tab="{{ $screen['key'] ?? $index }}">
                    {{ $screen['label'] ?? '' }}
                </button>
            @endforeach
        </div>
        <div class="mkp-field-ops-stage">
            <div class="mkp-phone" aria-hidden="false">
                @foreach($fieldOps as $index => $screen)
                    <div class="mkp-field-ops-panel {{ $index === 0 ? 'is-active' : '' }}"
                         data-mkp-field-panel="{{ $screen['key'] ?? $index }}"
                         @if($index !== 0) hidden @endif>
                        @include('marketplace.partials.product-shot', [
                            'shot' => $screen['shot'],
                            'alt' => $screen['alt'] ?? ($screen['label'] ?? 'EXP Vendedor'),
                            'width' => 390,
                            'height' => 844,
                            'lazy' => $index !== 0,
                        ])
                    </div>
                @endforeach
            </div>
            <div class="mkp-field-ops-copy">
                @foreach($fieldOps as $index => $screen)
                    <p class="mkp-field-ops-caption {{ $index === 0 ? 'is-active' : '' }}"
                       data-mkp-field-copy="{{ $screen['key'] ?? $index }}"
                       @if($index !== 0) hidden @endif>
                        {{ $screen['copy'] ?? '' }}
                    </p>
                @endforeach
            </div>
        </div>
    </div>
@endif
