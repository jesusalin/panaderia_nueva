@extends('layouts.app')
@section('title', 'Nueva Materia Prima')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@include('materia_prima.partials._styles')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card mp-form-card">
            <div class="mp-form-header">
                <div class="mp-form-icon"><i class="fas fa-wheat-awn"></i></div>
                <div>
                    <h5>Nueva Materia Prima</h5>
                    <p>Insumo que se usará en tus recetas de producción</p>
                </div>
            </div>
            <div class="mp-form-body">
                <form action="{{ route('materia-prima.store') }}" method="POST">@csrf

                    <label class="section-label"><i class="fas fa-info-circle mr-1"></i> Información básica</label>
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="inNombre" class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre') }}" placeholder="Ej: Harina de trigo" required>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Unidad de Medida <span class="text-danger">*</span></label>
                            <select name="id_unidad" id="inUnidad" class="form-control @error('id_unidad') is-invalid @enderror" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach($unidades as $u)
                                    <option value="{{ $u->id }}" data-abrev="{{ $u->abreviatura }}" data-nombre="{{ $u->nombre }}" {{ old('id_unidad') == $u->id ? 'selected' : '' }}>
                                        {{ $u->nombre }} ({{ $u->abreviatura }})
                                    </option>
                                @endforeach
                            </select>
                            @error('id_unidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <label class="section-label"><i class="fas fa-balance-scale mr-1"></i> Stock y costo</label>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Stock Actual <span class="text-danger">*</span></label>
                            <input type="number" name="stock_actual" id="inStock" step="0.001" min="0" class="form-control"
                                value="{{ old('stock_actual', 0) }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Stock Mínimo <span class="text-danger">*</span></label>
                            <input type="number" name="stock_minimo" id="inStockMin" step="0.001" min="0" class="form-control"
                                value="{{ old('stock_minimo', 0) }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Costo Unitario <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                                <input type="number" name="costo_unitario" id="inCosto" step="0.01" min="0" class="form-control"
                                    value="{{ old('costo_unitario', 0) }}" required>
                            </div>
                        </div>
                    </div>

                    <label class="section-label"><i class="fas fa-truck mr-1"></i> Reposición</label>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Proveedor Principal</label>
                            <select name="id_proveedor" id="inProveedor" class="form-control">
                                <option value="">-- Sin asignar --</option>
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id }}" {{ old('id_proveedor') == $prov->id ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                                @endforeach
                            </select>
                            <div class="repo-hint">Necesario para generar órdenes automáticas de reposición</div>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Cantidad de Reposición</label>
                            <input type="number" name="cantidad_reposicion" step="0.001" min="0" class="form-control"
                                value="{{ old('cantidad_reposicion') }}" placeholder="Cantidad sugerida a comprar">
                            <div class="repo-hint">Si se deja vacío, se sugiere el doble del stock mínimo</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" id="inEstado" class="form-control">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('materia-prima.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Vista previa en vivo --}}
    <div class="col-lg-4">
        <div class="preview-wrap">
            <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así se verá en el listado</span>
            <div class="item-card">
                <div class="item-card-media" style="height:80px;"><i class="fas fa-wheat-awn"></i></div>
                <div class="item-card-body">
                    <div class="item-card-cat" id="prevProveedor">Sin proveedor asignado</div>
                    <h3 class="item-card-title" id="prevNombre">Nombre del insumo</h3>
                    <div class="stock-gauge">
                        <div class="sg-track"><div class="sg-fill ok" id="prevGaugeFill" style="width:0%;"></div></div>
                        <div class="sg-labels">
                            <span>Actual: <strong><span id="prevStock">0</span> <span id="prevUnidad1"></span></strong></span>
                            <span>Mínimo: <span id="prevStockMin">0</span> <span id="prevUnidad2"></span></span>
                        </div>
                    </div>
                    <div class="item-card-price" style="font-size:1.05rem;">
                        S/ <span id="prevCosto">0.00</span>
                        <span class="ic-cost">por <span id="prevUnidadNombre">unidad</span></span>
                    </div>
                </div>
                <div class="item-card-footer">
                    <span class="badge-soft badge-soft-success" id="prevEstado">Activo</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const $ = id => document.getElementById(id);

    function actualizarPreview() {
        $('prevNombre').textContent = $('inNombre').value.trim() || 'Nombre del insumo';

        const provSel = $('inProveedor');
        $('prevProveedor').textContent = provSel.value ? provSel.options[provSel.selectedIndex].text : 'Sin proveedor asignado';

        const unidadSel = $('inUnidad');
        const opt = unidadSel.options[unidadSel.selectedIndex];
        const abrev = opt && opt.dataset.abrev ? opt.dataset.abrev : '';
        const nombreUnidad = opt && opt.dataset.nombre ? opt.dataset.nombre : 'unidad';
        $('prevUnidad1').textContent = abrev;
        $('prevUnidad2').textContent = abrev;
        $('prevUnidadNombre').textContent = nombreUnidad;

        const stock = parseFloat($('inStock').value) || 0;
        const stockMin = parseFloat($('inStockMin').value) || 0;
        $('prevStock').textContent = stock.toFixed(2);
        $('prevStockMin').textContent = stockMin.toFixed(2);

        const referencia = Math.max(stockMin * 2, 0.001);
        const porcentaje = Math.min(100, Math.round((stock / referencia) * 100));
        const bajo = stock <= stockMin;
        $('prevGaugeFill').style.width = porcentaje + '%';
        $('prevGaugeFill').className = 'sg-fill ' + (bajo ? 'bajo' : 'ok');

        $('prevCosto').textContent = (parseFloat($('inCosto').value) || 0).toFixed(2);

        const activo = $('inEstado').value === 'activo';
        $('prevEstado').textContent = activo ? 'Activo' : 'Inactivo';
        $('prevEstado').className = 'badge-soft ' + (activo ? 'badge-soft-success' : 'badge-soft-secondary');
    }

    ['inNombre','inUnidad','inStock','inStockMin','inCosto','inProveedor','inEstado'].forEach(id => {
        $(id).addEventListener('input', actualizarPreview);
        $(id).addEventListener('change', actualizarPreview);
    });

    actualizarPreview();
</script>
@endpush
