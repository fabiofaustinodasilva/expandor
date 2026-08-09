@extends('layouts.operational')

@section('title', $editing ? 'Editar produto' : 'Novo produto')

@section('page')
    <h1 class="page-title">{{ $editing ? 'Editar produto' : 'Novo produto' }}</h1>
    <p class="header-meta" style="margin-bottom:1rem;">Catálogo genérico — planos, equipamentos ou qualquer item comercial.</p>

    <form method="POST"
          action="{{ $editing ? route('commissions.products.update', $product) : route('commissions.products.store') }}"
          class="card" style="max-width:640px;"
          enctype="multipart/form-data">
        @csrf
        @if($editing) @method('PUT') @endif

        <div style="margin-bottom:.75rem;">
            <label for="name">Nome do produto / plano</label>
            <input class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" required maxlength="255">
            @error('name')<div class="header-meta" style="color:var(--highlight);">{{ $message }}</div>@enderror
        </div>

        <div class="grid grid-2" style="gap:.75rem;margin-bottom:.75rem;">
            <div>
                <label for="category">Categoria</label>
                <input class="form-control" id="category" name="category" value="{{ old('category', $product->category) }}" maxlength="80" placeholder="Ex.: Móvel, Fibra">
            </div>
            <div>
                <label for="sort_order">Ordem de exibição</label>
                <input class="form-control" type="number" min="0" id="sort_order" name="sort_order"
                       value="{{ old('sort_order', $product->sort_order ?? 0) }}">
            </div>
        </div>

        <div style="margin-bottom:.75rem;">
            <label for="description">Descrição</label>
            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
        </div>

        <div style="margin-bottom:.75rem;">
            <label for="benefits">Benefícios (um por linha)</label>
            <textarea class="form-control" id="benefits" name="benefits" rows="4" placeholder="5 GB&#10;Ligações ilimitadas">{{ old('benefits', is_array($product->benefits) ? implode("\n", $product->benefits) : '') }}</textarea>
        </div>

        <x-media-upload
            name="image"
            label="Imagem do produto"
            :current-url="$product->imageUrl()"
            :remove-name="$editing ? 'remove_image' : null"
            accept="image/jpeg,image/png,image/webp"
            hint="JPG, PNG ou WEBP até 5MB. Thumbnail gerado automaticamente."
        />

        <div style="margin-bottom:.75rem;">
            <label for="video_url">Vídeo (URL)</label>
            <input class="form-control" id="video_url" name="video_url" type="url"
                   value="{{ old('video_url', $product->video_url) }}"
                   placeholder="https://… (MP4 direto, YouTube ou Vimeo)">
            <div class="header-meta">Upload de arquivo de vídeo não está no pipeline de mídia atual — use URL.</div>
            @error('video_url')<div class="header-meta" style="color:var(--highlight);">{{ $message }}</div>@enderror
        </div>
        <div class="grid grid-2" style="gap:.75rem;margin-bottom:.75rem;">
            <div>
                <label for="price">Valor de venda (R$)</label>
                <input class="form-control" type="number" step="0.01" min="0" id="price" name="price"
                       value="{{ old('price', $product->price) }}" required>
            </div>
            <div>
                <label for="commission_type">Como deseja pagar a comissão?</label>
                @php
                    $commissionType = old('commission_type', $product->commission_type?->value ?? $product->commission_type ?? 'fixed');
                @endphp
                <select class="form-control" id="commission_type" name="commission_type" required>
                    <option value="fixed" @selected($commissionType === 'fixed')>Valor fixo</option>
                    <option value="percentage" @selected($commissionType === 'percentage')>Porcentagem</option>
                </select>
            </div>
        </div>

        <fieldset style="border:1px solid var(--border, #d4d4d8);border-radius:8px;padding:.75rem 1rem;margin-bottom:.75rem;">
            <legend style="padding:0 .35rem;font-size:.85rem;font-weight:600;">COMISSÃO</legend>

            <div id="commission-fixed-fields" style="margin-bottom:.75rem;">
                <label for="commission_amount">{{ $commercial::commissionPerSale() }} (R$)</label>
                <input class="form-control" type="number" step="0.01" min="0" id="commission_amount" name="commission_amount"
                       value="{{ old('commission_amount', $product->commission_amount ?? 0) }}">
                @error('commission_amount')<div class="header-meta" style="color:var(--highlight);">{{ $message }}</div>@enderror
            </div>

            <div id="commission-percentage-fields" style="margin-bottom:.75rem;">
                <label for="commission_percentage">Percentual (%)</label>
                <input class="form-control" type="number" step="0.01" min="0" max="100" id="commission_percentage" name="commission_percentage"
                       value="{{ old('commission_percentage', $product->commission_percentage) }}">
                @error('commission_percentage')<div class="header-meta" style="color:var(--highlight);">{{ $message }}</div>@enderror
            </div>

            <div class="header-meta" id="commission-preview" aria-live="polite">
                Preço atual do produto: R$ <span id="commission-preview-price">0,00</span><br>
                Comissão estimada: R$ <span id="commission-preview-amount">0,00</span>
            </div>
        </fieldset>

        @push('scripts')
        <script>
            (function () {
                const typeEl = document.getElementById('commission_type');
                const priceEl = document.getElementById('price');
                const amountEl = document.getElementById('commission_amount');
                const pctEl = document.getElementById('commission_percentage');
                const fixedBox = document.getElementById('commission-fixed-fields');
                const pctBox = document.getElementById('commission-percentage-fields');
                const previewPrice = document.getElementById('commission-preview-price');
                const previewAmount = document.getElementById('commission-preview-amount');

                function money(n) {
                    return (Math.round((Number(n) || 0) * 100) / 100).toFixed(2).replace('.', ',');
                }

                function syncType() {
                    const isPct = typeEl.value === 'percentage';
                    fixedBox.style.display = isPct ? 'none' : '';
                    pctBox.style.display = isPct ? '' : 'none';
                    amountEl.required = !isPct;
                    pctEl.required = isPct;
                    updatePreview();
                }

                function updatePreview() {
                    const price = Number(priceEl.value) || 0;
                    previewPrice.textContent = money(price);
                    if (typeEl.value === 'percentage') {
                        const pct = Number(pctEl.value) || 0;
                        previewAmount.textContent = money(price * (pct / 100));
                    } else {
                        previewAmount.textContent = money(amountEl.value);
                    }
                }

                typeEl.addEventListener('change', syncType);
                priceEl.addEventListener('input', updatePreview);
                amountEl.addEventListener('input', updatePreview);
                pctEl.addEventListener('input', updatePreview);
                syncType();
            })();
        </script>
        @endpush

        <div style="margin-bottom:.75rem;">
            <label>
                <input type="checkbox" name="stock_control" value="1" id="stock_control"
                    @checked(old('stock_control', $product->stock_control))>
                Controla estoque
            </label>
        </div>

        <div class="grid grid-2" style="gap:.75rem;margin-bottom:.75rem;" id="stock-fields">
            @unless($editing)
                <div>
                    <label for="stock_quantity">Estoque inicial</label>
                    <input class="form-control" type="number" min="0" id="stock_quantity" name="stock_quantity"
                           value="{{ old('stock_quantity', $product->stock_quantity) }}">
                </div>
            @endunless
            <div>
                <label for="minimum_stock">Estoque mínimo</label>
                <input class="form-control" type="number" min="0" id="minimum_stock" name="minimum_stock"
                       value="{{ old('minimum_stock', $product->minimum_stock) }}">
            </div>
        </div>

        <div style="margin-bottom:1rem;">
            <label for="status">Status</label>
            <select class="form-control" id="status" name="status" required>
                <option value="active" @selected(old('status', $product->status) === 'active')>Ativo</option>
                <option value="inactive" @selected(old('status', $product->status) === 'inactive')>Inativo</option>
            </select>
        </div>

        <div style="display:flex;gap:.5rem;">
            <button class="btn btn-primary" type="submit">Salvar</button>
            <a class="btn btn-ghost" href="{{ route('commissions.products.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
