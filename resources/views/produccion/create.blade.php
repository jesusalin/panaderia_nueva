@extends('layouts.app')
@section('title', 'Nueva Producción')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('produccion.index') }}">Producción</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@section('content')
<form action="{{ route('produccion.store') }}" method="POST" id="formProduccion">
@csrf
<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-industry mr-2 text-warning"></i>Datos de Producción</h5>
            </div>
            <div class="card-body">
                @if($errors->any())
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}
                </div>
                @endif

                <div class="form-group">
                    <label>Producto <span class="text-danger">*</span></label>
                    <select name="id_producto" id="selectProducto"
                        class="form-control @error('id_producto') is-invalid @enderror" required>
                        <option value="">-- Seleccionar producto --</option>
                        @foreach($productos as $p)
                            <option value="{{ $p->id }}" {{ old('id_producto') == $p->id ? 'selected' : '' }}>
                                {{ $p->nombre }}
                                @if($p->receta) (receta: {{ $p->receta->rendimiento }} und.) @else — SIN RECETA @endif
                            </option>
                        @endforeach
                    </select>
                    @error('id_producto')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

                <div class="form-group">
                    <label>Observación</label>
                    <textarea name="observacion" class="form-control" rows="2"
                        placeholder="Opcional...">{{ old('observacion') }}</textarea>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="{{ route('produccion.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-warning" id="btnGuardar">
                    <i class="fas fa-industry mr-1"></i>Registrar Producción
                </button>
            </div>
        </div>
    </div>

    {{-- Panel de ingredientes necesarios --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Ingredientes necesarios</h5>
            </div>
            <div class="card-body" id="panelIngredientes">
                <p class="text-muted text-center py-3">
                    <i class="fas fa-arrow-left mr-1"></i>Selecciona un producto para ver su receta.
                </p>
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

function actualizarPanel() {
    const id       = selectProducto.value;
    const cantidad = parseInt(inputCantidad.value) || 1;

    if (!id) {
        panel.innerHTML = '<p class="text-muted text-center py-3"><i class="fas fa-arrow-left mr-1"></i>Selecciona un producto para ver su receta.</p>';
        return;
    }

    panel.innerHTML = '<p class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin mr-1"></i>Cargando receta...</p>';

    fetch(`/produccion/ingredientes/${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data) {
                panel.innerHTML = '<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Este producto no tiene receta. <a href="{{ route("produccion.recetas") }}">Crear receta</a></div>';
                return;
            }

            const lotes = cantidad / Math.max(data.rendimiento, 1);
            let html = `<p class="small text-muted mb-2">Rendimiento base: <strong>${data.rendimiento} und.</strong> — Produciendo <strong>${cantidad} und.</strong> (${lotes.toFixed(2)} lotes)</p>`;
            html += '<table class="table table-sm mb-0">';
            html += '<thead class="bg-light"><tr><th>Ingrediente</th><th class="text-right">Necesario</th><th class="text-right">Stock</th></tr></thead><tbody>';

            let sinStock = false;
            data.detalles.forEach(d => {
                const necesario = (d.cantidad * lotes).toFixed(3);
                const ok        = parseFloat(d.stock_actual) >= parseFloat(necesario);
                if (!ok) sinStock = true;
                const clase = ok ? 'text-success' : 'text-danger font-weight-bold';
                html += `<tr>
                    <td>${d.nombre}</td>
                    <td class="text-right">${necesario} ${d.abreviatura}</td>
                    <td class="text-right ${clase}">${d.stock_actual} ${d.abreviatura}</td>
                </tr>`;
            });

            html += '</tbody></table>';

            if (sinStock) {
                html += '<div class="alert alert-danger mt-2 mb-0 small"><i class="fas fa-times-circle mr-1"></i>Stock insuficiente para algunos ingredientes.</div>';
                document.getElementById('btnGuardar').disabled = true;
            } else {
                html += '<div class="alert alert-success mt-2 mb-0 small"><i class="fas fa-check-circle mr-1"></i>Stock suficiente para producir.</div>';
                document.getElementById('btnGuardar').disabled = false;
            }

            panel.innerHTML = html;
        })
        .catch(() => {
            panel.innerHTML = '<div class="alert alert-danger mb-0">Error al cargar la receta.</div>';
        });
}

selectProducto.addEventListener('change', actualizarPanel);
inputCantidad.addEventListener('input', actualizarPanel);
</script>
@endpush
