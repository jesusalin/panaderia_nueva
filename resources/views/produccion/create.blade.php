@extends('layouts.app')
@section('title', 'Nueva Producción')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('produccion.index') }}">Producción</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@push('styles')
<style>
    .prod-form-card { border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.05); }
    body.dark-mode .prod-form-card { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .prod-form-header {
        background: linear-gradient(135deg, #b5451b, #8a3213); color: #fff; padding: 1.1rem 1.4rem;
        display: flex; align-items: center; gap: .7rem;
    }
    .prod-form-header .pfh-icon {
        width: 42px; height: 42px; border-radius: 10px; background: rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
    }
    .prod-form-header h5 { margin: 0; font-weight: 800; }
    .prod-form-header small { display: block; opacity: .85; font-weight: 400; }
    .prod-form-body { padding: 1.4rem; }
    body.dark-mode .prod-form-body label { color: #d5d5e2; }

    .prod-preview {
        display: none; align-items: center; gap: .7rem; margin-top: .6rem; padding: .6rem .8rem;
        background: #f7f5f3; border-radius: 10px; border: 1px solid #eee;
    }
    body.dark-mode .prod-preview { background: #24243b; border-color: #33334d; }
    .prod-preview.mostrar { display: flex; }
    .prod-preview-icon {
        width: 40px; height: 40px; border-radius: 9px; background: #fff; color: #b5451b;
        display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; overflow: hidden;
    }
    body.dark-mode .prod-preview-icon { background: #1f1f33; color: #ff9d6e; }
    .prod-preview-icon img { width: 100%; height: 100%; object-fit: cover; }
    .prod-preview-info strong { display: block; color: #1a1a2e; font-size: .92rem; }
    body.dark-mode .prod-preview-info strong { color: #f0f0f7; }
    .prod-preview-info span { font-size: .78rem; color: #8a8a9d; }

    .prod-form-actions { padding: 1rem 1.4rem; border-top: 1px solid #eee; text-align: right; }
    body.dark-mode .prod-form-actions { border-top-color: #33334d; }

    /* Panel de ingredientes */
    .prod-recipe-summary { font-size: .82rem; color: #8a8a9d; margin-bottom: .9rem; }
    body.dark-mode .prod-recipe-summary { color: #9a9ac0; }
    .prod-recipe-summary strong { color: #1a1a2e; }
    body.dark-mode .prod-recipe-summary strong { color: #f0f0f7; }

    .ing-row {
        display: flex; align-items: center; gap: .7rem; padding: .6rem 0; border-bottom: 1px solid #f1f1f4;
    }
    .ing-row:last-child { border-bottom: none; }
    body.dark-mode .ing-row { border-bottom-color: #2c2c44; }
    .ing-icon {
        width: 32px; height: 32px; border-radius: 8px; background: #f7f5f3; color: #b5451b;
        display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
    }
    body.dark-mode .ing-icon { background: #24243b; color: #ff9d6e; }
    .ing-body { flex: 1; min-width: 0; }
    .ing-nombre { font-weight: 700; font-size: .86rem; color: #1a1a2e; }
    body.dark-mode .ing-nombre { color: #f0f0f7; }
    .ing-bar { height: 5px; border-radius: 4px; background: #eef0f3; overflow: hidden; margin-top: .35rem; }
    body.dark-mode .ing-bar { background: #33334d; }
    .ing-bar span { display: block; height: 100%; border-radius: 4px; }
    .ing-cifras { text-align: right; font-size: .8rem; white-space: nowrap; flex-shrink: 0; }
    .ing-cifras .ing-necesario { font-weight: 700; }
    .ing-cifras .ing-stock { display: block; font-size: .72rem; color: #adb5bd; }

    .prod-banner {
        display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem; border-radius: 10px;
        font-size: .85rem; font-weight: 600; margin-top: 1rem;
    }
    .prod-banner.ok   { background: rgba(46,204,113,.12); color: #1e8e5a; }
    .prod-banner.warn { background: rgba(243,156,18,.12); color: #b9770e; }
    .prod-banner.bad  { background: rgba(231,76,60,.12); color: #c0392b; }
    body.dark-mode .prod-banner.ok   { background: rgba(46,204,113,.16); color: #6ee7a5; }
    body.dark-mode .prod-banner.warn { background: rgba(243,156,18,.18); color: #ffc673; }
    body.dark-mode .prod-banner.bad  { background: rgba(231,76,60,.18); color: #ff9b8f; }

    .prod-empty-panel { text-align: center; padding: 2.5rem 1rem; color: #adb5bd; }
    .prod-empty-panel i { font-size: 2.2rem; margin-bottom: .7rem; opacity: .5; display: block; }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-industry mr-2 text-warning"></i>Nueva Producción</h2>
        <p>Registra una producción y descuenta automáticamente los insumos según la receta</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('produccion.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Volver
        </a>
    </div>
</div>

<form action="{{ route('produccion.store') }}" method="POST" id="formProduccion">
@csrf
<div class="row">
    <div class="col-md-7">
        <div class="prod-form-card">
            <div class="prod-form-header">
                <div class="pfh-icon"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <h5>Datos de producción</h5>
                    <small>Elige el producto y la cantidad a fabricar</small>
                </div>
            </div>
            <div class="prod-form-body">
                @if($errors->any())
                <div class="prod-banner bad" style="margin-top:0; margin-bottom:1rem;">
                    <i class="fas fa-exclamation-circle"></i>{{ $errors->first() }}
                </div>
                @endif

                <div class="form-group">
                    <label>Producto <span class="text-danger">*</span></label>
                    <select name="id_producto" id="selectProducto"
                        class="form-control @error('id_producto') is-invalid @enderror" required>
                        <option value="">-- Seleccionar producto --</option>
                        @foreach($productos as $p)
                            @php
                                $catLower = mb_strtolower($p->categoria->nombre ?? '');
                                $iconoP = 'fa-bread-slice';
                                if (str_contains($catLower, 'pastel') || str_contains($catLower, 'torta')) $iconoP = 'fa-birthday-cake';
                                elseif (str_contains($catLower, 'galleta'))  $iconoP = 'fa-cookie';
                                elseif (str_contains($catLower, 'empanada')) $iconoP = 'fa-cheese';
                                elseif (str_contains($catLower, 'bebida') || str_contains($catLower, 'café')) $iconoP = 'fa-mug-hot';
                            @endphp
                            <option value="{{ $p->id }}" {{ old('id_producto') == $p->id ? 'selected' : '' }}
                                data-nombre="{{ $p->nombre }}"
                                data-categoria="{{ $p->categoria->nombre ?? 'Sin categoría' }}"
                                data-icono="{{ $iconoP }}"
                                data-imagen="{{ $p->imagen ? asset('storage/'.$p->imagen) : '' }}">
                                {{ $p->nombre }}
                                @if($p->receta) (receta: {{ $p->receta->rendimiento }} und.) @else — SIN RECETA @endif
                            </option>
                        @endforeach
                    </select>
                    @error('id_producto')<div class="invalid-feedback">{{ $message }}</div>@enderror

                    <div class="prod-preview" id="prodPreview">
                        <div class="prod-preview-icon" id="prodPreviewIcon"></div>
                        <div class="prod-preview-info">
                            <strong id="prodPreviewNombre"></strong>
                            <span id="prodPreviewCat"></span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Cantidad a producir <span class="text-danger">*</span></label>
                        <input type="number" name="cantidad" id="inputCantidad"
                            class="form-control @error('cantidad') is-invalid @enderror"
                            value="{{ old('cantidad', 1) }}" min="1" required>
                        @error('cantidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha"
                            class="form-control @error('fecha') is-invalid @enderror"
                            value="{{ old('fecha', date('Y-m-d')) }}" required>
                        @error('fecha')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label>Observación</label>
                    <textarea name="observacion" class="form-control" rows="2"
                        placeholder="Opcional...">{{ old('observacion') }}</textarea>
                </div>
            </div>
            <div class="prod-form-actions">
                <a href="{{ route('produccion.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-warning" id="btnGuardar">
                    <i class="fas fa-industry mr-1"></i>Registrar Producción
                </button>
            </div>
        </div>
    </div>

    {{-- Panel de ingredientes necesarios --}}
    <div class="col-md-5">
        <div class="prod-form-card">
            <div class="prod-form-header" style="background:linear-gradient(135deg,#1a1a2e,#33334d);">
                <div class="pfh-icon"><i class="fas fa-flask"></i></div>
                <div>
                    <h5>Ingredientes necesarios</h5>
                    <small>Se calculan según la receta del producto</small>
                </div>
            </div>
            <div class="prod-form-body" id="panelIngredientes">
                <div class="prod-empty-panel">
                    <i class="fas fa-arrow-left"></i>
                    <p class="mb-0">Selecciona un producto para ver su receta.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const selectProducto = document.getElementById('selectProducto');
const inputCantidad  = document.getElementById('inputCantidad');
const panel          = document.getElementById('panelIngredientes');
const preview        = document.getElementById('prodPreview');

function actualizarPreview() {
    const opt = selectProducto.options[selectProducto.selectedIndex];
    if (!opt || !opt.value) { preview.classList.remove('mostrar'); return; }

    const icono  = opt.dataset.icono || 'fa-bread-slice';
    const imagen = opt.dataset.imagen;
    document.getElementById('prodPreviewIcon').innerHTML = imagen
        ? `<img src="${imagen}" alt="">`
        : `<i class="fas ${icono}"></i>`;
    document.getElementById('prodPreviewNombre').textContent = opt.dataset.nombre;
    document.getElementById('prodPreviewCat').textContent    = opt.dataset.categoria;
    preview.classList.add('mostrar');
}

function actualizarPanel() {
    const id       = selectProducto.value;
    const cantidad = parseInt(inputCantidad.value) || 1;

    actualizarPreview();

    if (!id) {
        panel.innerHTML = '<div class="prod-empty-panel"><i class="fas fa-arrow-left"></i><p class="mb-0">Selecciona un producto para ver su receta.</p></div>';
        return;
    }

    panel.innerHTML = '<div class="prod-empty-panel"><i class="fas fa-spinner fa-spin"></i><p class="mb-0">Cargando receta...</p></div>';

    fetch(`/produccion/ingredientes/${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data) {
                panel.innerHTML = `<div class="prod-banner bad" style="margin-top:0;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Este producto no tiene receta. <a href="{{ route('produccion.recetas') }}">Crear receta</a>
                </div>`;
                document.getElementById('btnGuardar').disabled = true;
                return;
            }

            const lotes = cantidad / Math.max(data.rendimiento, 1);
            let html = `<p class="prod-recipe-summary">Rendimiento base: <strong>${data.rendimiento} und.</strong> — Produciendo <strong>${cantidad} und.</strong> (${lotes.toFixed(2)} lotes)</p>`;

            let sinStock = false;
            data.detalles.forEach(d => {
                const necesario = parseFloat((d.cantidad * lotes).toFixed(3));
                const stock     = parseFloat(d.stock_actual);
                const ok        = stock >= necesario;
                if (!ok) sinStock = true;
                const pct   = necesario > 0 ? Math.min((stock / necesario) * 100, 100) : 100;
                const color = ok ? '#2ecc71' : '#e74c3c';
                html += `
                    <div class="ing-row">
                        <div class="ing-icon"><i class="fas fa-wheat-awn"></i></div>
                        <div class="ing-body">
                            <div class="ing-nombre">${d.nombre}</div>
                            <div class="ing-bar"><span style="width:${pct}%; background:${color};"></span></div>
                        </div>
                        <div class="ing-cifras">
                            <span class="ing-necesario" style="color:${color};">${necesario} ${d.abreviatura}</span>
                            <span class="ing-stock">stock: ${d.stock_actual} ${d.abreviatura}</span>
                        </div>
                    </div>`;
            });

            if (sinStock) {
                html += '<div class="prod-banner bad"><i class="fas fa-times-circle"></i>Stock insuficiente para algunos ingredientes.</div>';
                document.getElementById('btnGuardar').disabled = true;
            } else {
                html += '<div class="prod-banner ok"><i class="fas fa-check-circle"></i>Stock suficiente para producir.</div>';
                document.getElementById('btnGuardar').disabled = false;
            }

            panel.innerHTML = html;
        })
        .catch(() => {
            panel.innerHTML = '<div class="prod-banner bad" style="margin-top:0;"><i class="fas fa-exclamation-circle"></i>Error al cargar la receta.</div>';
        });
}

selectProducto.addEventListener('change', actualizarPanel);
inputCantidad.addEventListener('input', actualizarPanel);

@if(old('id_producto'))
actualizarPanel();
@endif
</script>
@endpush
