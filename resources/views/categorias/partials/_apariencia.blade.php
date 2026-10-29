{{-- Ícono y color de la categoría. Uso: @include('categorias.partials._apariencia', ['categoria' => $categoria ?? null]) --}}
@php
    $iconoSel = old('icono', $categoria?->icono ?? '');
    $colorSel = old('color', $categoria?->color ?? \App\Models\Categoria::COLOR_DEFECTO);
@endphp
<div class="form-group">
    <label>Ícono <small class="text-muted">(si no eliges, se adivina por el nombre)</small></label>
    <div class="ap-iconos" role="radiogroup" aria-label="Ícono de la categoría">
        <label class="ap-icono" title="Automático">
            <input type="radio" name="icono" value="" {{ $iconoSel === '' || $iconoSel === null ? 'checked' : '' }}>
            <span><i class="fas fa-magic"></i></span>
        </label>
        @foreach(\App\Models\Categoria::ICONOS as $clase => $nombre)
            <label class="ap-icono" title="{{ $nombre }}">
                <input type="radio" name="icono" value="{{ $clase }}" {{ $iconoSel === $clase ? 'checked' : '' }}>
                <span><i class="fas {{ $clase }}"></i></span>
            </label>
        @endforeach
    </div>
    @error('icono')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label>Color</label>
    <div class="ap-colores">
        @foreach(\App\Models\Categoria::COLORES as $hex)
            <label class="ap-color" title="{{ $hex }}">
                <input type="radio" name="color" value="{{ $hex }}" {{ strtolower($colorSel) === $hex ? 'checked' : '' }}>
                <span style="background: {{ $hex }}"></span>
            </label>
        @endforeach
        <label class="ap-color ap-color-libre" title="Otro color">
            <input type="radio" name="color" value="{{ in_array(strtolower($colorSel), \App\Models\Categoria::COLORES) ? '#ffffff' : $colorSel }}"
                {{ in_array(strtolower($colorSel), \App\Models\Categoria::COLORES) ? '' : 'checked' }} id="apColorLibreRadio">
            <input type="color" id="apColorLibre" value="{{ in_array(strtolower($colorSel), \App\Models\Categoria::COLORES) ? '#ffffff' : $colorSel }}" aria-label="Elegir otro color">
            <span class="ap-libre-txt">Otro</span>
        </label>
    </div>
    @error('color')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

<div class="form-group">
    <label>Vista previa</label>
    <div class="ap-preview">
        <div class="ap-preview-icono" id="apPreviewIcono"><i class="fas fa-tag"></i></div>
        <div>
            <div class="ap-preview-nombre" id="apPreviewNombre">Nombre de la categoría</div>
            <small class="text-muted">Así se verá en el listado</small>
        </div>
    </div>
</div>

@push('styles')
<style>
    .ap-iconos { display: grid; grid-template-columns: repeat(auto-fill, minmax(42px, 1fr)); gap: .4rem; }
    .ap-icono input, .ap-color input[type=radio] { position: absolute; opacity: 0; pointer-events: none; }
    .ap-icono { margin: 0; cursor: pointer; }
    .ap-icono span { display: flex; align-items: center; justify-content: center; height: 42px; border-radius: 10px; border: 1.5px solid #e9ecef; color: #6c757d; font-size: 1.05rem; transition: all .12s; }
    .ap-icono:hover span { border-color: #d9c2b5; color: #1a1a2e; }
    .ap-icono input:checked + span { border-color: var(--ap-color, #b5451b); background: var(--ap-color, #b5451b); color: #fff; }
    .ap-icono input:focus-visible + span { box-shadow: 0 0 0 3px rgba(181,69,27,.25); }
    .ap-colores { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .ap-color { margin: 0; cursor: pointer; position: relative; }
    .ap-color input[type=radio] + span { display: block; width: 32px; height: 32px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 1.5px #dee2e6; transition: transform .12s; }
    .ap-color input:checked + span { box-shadow: 0 0 0 2.5px #1a1a2e; transform: scale(1.08); }
    .ap-color-libre { display: flex; align-items: center; gap: .35rem; font-size: .78rem; color: #8a8a9d; }
    .ap-color-libre input[type=color] { width: 32px; height: 32px; padding: 0; border: 2px dashed #ced4da; border-radius: 50%; background: none; cursor: pointer; overflow: hidden; }
    .ap-color-libre input[type=color]::-webkit-color-swatch-wrapper { padding: 2px; }
    .ap-color-libre input[type=color]::-webkit-color-swatch { border: none; border-radius: 50%; }
    .ap-color-libre input[type=color]::-moz-color-swatch { border: none; border-radius: 50%; }
    .ap-color-libre input[type=radio]:checked ~ input[type=color] { outline: 2.5px solid #1a1a2e; outline-offset: 2px; }
    .ap-preview { display: flex; align-items: center; gap: .85rem; padding: .8rem 1rem; border: 1.5px dashed #e9ecef; border-radius: 12px; }
    .ap-preview-icono { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.3rem; }
    .ap-preview-nombre { font-weight: 800; color: #1a1a2e; }
    body.dark-mode .ap-preview-nombre { color: #f0f0f7; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var form = document.querySelector('.ap-iconos').closest('form');
    var nombre = form.querySelector('[name=nombre]');
    var libre = document.getElementById('apColorLibre'), libreRadio = document.getElementById('apColorLibreRadio');
    var adivinar = function (n) {
        n = (n || '').toLowerCase();
        if (/pastel|torta|queque/.test(n)) return 'fa-birthday-cake';
        if (/galleta/.test(n)) return 'fa-cookie';
        if (/empanada|salad/.test(n)) return 'fa-cheese';
        if (/bebida|caf[eé]/.test(n)) return 'fa-mug-hot';
        if (/pan/.test(n)) return 'fa-bread-slice';
        if (/postre|dulce/.test(n)) return 'fa-candy-cane';
        return 'fa-tag';
    };
    function pintar() {
        var color = (form.querySelector('[name=color]:checked') || {}).value || '{{ \App\Models\Categoria::COLOR_DEFECTO }}';
        var icono = (form.querySelector('[name=icono]:checked') || {}).value || adivinar(nombre.value);
        var caja = document.getElementById('apPreviewIcono');
        caja.style.background = 'linear-gradient(135deg, #1a1a2e, ' + color + ')';
        caja.innerHTML = '<i class="fas ' + icono + '"></i>';
        document.getElementById('apPreviewNombre').textContent = nombre.value || 'Nombre de la categoría';
        form.style.setProperty('--ap-color', color);
    }
    libre.addEventListener('input', function () { libreRadio.value = libre.value; libreRadio.checked = true; pintar(); });
    libre.addEventListener('click', function () { libreRadio.value = libre.value; libreRadio.checked = true; pintar(); });
    form.addEventListener('change', pintar);
    nombre.addEventListener('input', pintar);
    pintar();
})();
</script>
@endpush
