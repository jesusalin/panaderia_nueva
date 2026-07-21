@extends('layouts.app')
@section('title', 'Nueva Compra')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@include('productos.partials._styles')

@push('styles')
<style>
    .detalle-card { border: none; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); overflow: hidden; }
    .detalle-card .card-header {
        background: #fff; border-bottom: 1px solid #f2f2f2; display: flex; align-items: center; justify-content: space-between;
    }
    body.dark-mode .detalle-card { background: #1f1f33; }
    body.dark-mode .detalle-card .card-header { background: #1f1f33; border-bottom-color: #33334d; }
    #tablaDetalle thead th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8a8a9d; font-weight: 800; border-top: none; }
    body.dark-mode #tablaDetalle thead th { color: #9a9ac0; }
    .resumen-box { background: #f7f5f3; border-radius: 12px; padding: 1rem 1.2rem; }
    body.dark-mode .resumen-box { background: #24243b; }
    .resumen-box .rb-total { font-size: 1.4rem; font-weight: 800; color: #b5451b; }
    .empty-detalle { text-align: center; padding: 2.5rem 1rem; color: #adb5bd; }
    .empty-detalle i { font-size: 2rem; margin-bottom: .5rem; display: block; opacity: .5; }
</style>
@endpush

@section('content')
<form action="{{ route('compras.store') }}" method="POST" id="formCompra">
@csrf
<div class="row">
    {{-- Cabecera --}}
    <div class="col-12">
        <div class="card prod-form-card mb-3">
            <div class="prod-form-header">
                <div class="prod-form-icon"><i class="fas fa-shopping-cart"></i></div>
                <div>
                    <h5>Nueva Compra</h5>
                    <p>Registra un pedido de materia prima a tu proveedor</p>
                </div>
            </div>
            <div class="prod-form-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Proveedor <span class="text-danger">*</span></label>
                        <select name="id_proveedor" class="form-control @error('id_proveedor') is-invalid @enderror" required>
                            <option value="">-- Seleccionar --</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" {{ old('id_proveedor') == $p->id ? 'selected' : '' }}>
                                    {{ $p->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_proveedor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Fecha de Compra <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_compra" class="form-control"
                            value="{{ old('fecha_compra', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Nro. Documento (Factura/Boleta)</label>
                        <input type="text" name="numero_doc" class="form-control"
                            value="{{ old('numero_doc') }}" placeholder="F001-000123">
                    </div>
                    <div class="col-md-12 form-group mb-0">
                        <label>Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="1">{{ old('observaciones') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detalle --}}
    <div class="col-12">
        <div class="card detalle-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list mr-2 text-warning"></i>Ingredientes Comprados</h5>
                <button type="button" class="btn btn-sm btn-success" id="btnAgregarFila">
                    <i class="fas fa-plus mr-1"></i>Agregar fila
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" id="tablaDetalle">
                    <thead>
                        <tr>
                            <th>Materia Prima</th>
                            <th style="width:150px">Cantidad</th>
                            <th style="width:150px">Precio Unitario</th>
                            <th style="width:130px" class="text-right">Subtotal</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody id="detalleBody"></tbody>
                </table>
                <div class="empty-detalle" id="emptyDetalle" style="display:none;">
                    <i class="fas fa-inbox"></i>
                    <p class="mb-0">Agrega al menos un ingrediente para poder registrar la compra</p>
                </div>
            </div>
            <div class="card-footer bg-white">
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <div class="resumen-box">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Subtotal</span><span id="resSubtotal">S/ 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small mb-2">
                                <span>IGV (18%)</span><span id="resIgv">S/ 0.00</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="border-top:1px solid #e5e0da;padding-top:.5rem;">
                                <span class="font-weight-bold">Total</span><span class="rb-total" id="resTotal">S/ 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 text-right">
                    <a href="{{ route('compras.index') }}" class="btn btn-light mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-warning" id="btnGuardar" disabled>
                        <i class="fas fa-save mr-1"></i>Registrar Compra
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
</form>

{{-- Template de fila --}}
<template id="filaTemplate">
    <tr class="fila-detalle">
        <td>
            <select name="materias[__INDEX__][id_materia]" class="form-control select-materia" required>
                <option value="">-- Seleccionar --</option>
                @foreach($materias as $m)
                    <option value="{{ $m->id }}" data-stock="{{ $m->stock_actual }}" data-costo="{{ $m->costo_unitario }}">
                        {{ $m->nombre }} ({{ $m->unidad->abreviatura }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted stock-hint" style="display:none;"></small>
        </td>
        <td>
            <input type="number" name="materias[__INDEX__][cantidad]" class="form-control input-cantidad"
                step="0.001" min="0.001" placeholder="0.000" required>
        </td>
        <td>
            <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                <input type="number" name="materias[__INDEX__][precio_unitario]" class="form-control input-precio"
                    step="0.01" min="0" placeholder="0.00" required>
            </div>
        </td>
        <td class="text-right align-middle subtotal-cell font-weight-bold">S/ 0.00</td>
        <td class="text-center align-middle">
            <button type="button" class="btn btn-icon btn-danger btn-quitar">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
let index = 0;

function recalcular() {
    let subtotal = 0;
    const filas = document.querySelectorAll('.fila-detalle');
    document.getElementById('emptyDetalle').style.display = filas.length === 0 ? 'block' : 'none';

    filas.forEach(fila => {
        const cant  = parseFloat(fila.querySelector('.input-cantidad').value) || 0;
        const precio= parseFloat(fila.querySelector('.input-precio').value)   || 0;
        const sub   = cant * precio;
        fila.querySelector('.subtotal-cell').textContent = 'S/ ' + sub.toFixed(2);
        subtotal += sub;
    });
    const igv   = subtotal * 0.18;
    const total = subtotal + igv;
    document.getElementById('resSubtotal').textContent = 'S/ ' + subtotal.toFixed(2);
    document.getElementById('resIgv').textContent      = 'S/ ' + igv.toFixed(2);
    document.getElementById('resTotal').textContent    = 'S/ ' + total.toFixed(2);
    document.getElementById('btnGuardar').disabled = filas.length === 0;
}

function agregarFila() {
    const template = document.getElementById('filaTemplate').innerHTML.replace(/__INDEX__/g, index++);
    const tbody = document.getElementById('detalleBody');
    tbody.insertAdjacentHTML('beforeend', template);
    const fila = tbody.lastElementChild;

    const select = fila.querySelector('.select-materia');
    const precioInput = fila.querySelector('.input-precio');
    const hint = fila.querySelector('.stock-hint');

    select.addEventListener('change', () => {
        const opt = select.selectedOptions[0];
        if (opt && opt.value) {
            hint.style.display = 'block';
            hint.textContent = 'Stock actual: ' + parseFloat(opt.dataset.stock);
            if (!precioInput.value) precioInput.value = opt.dataset.costo;
        } else {
            hint.style.display = 'none';
        }
        recalcular();
    });

    fila.querySelector('.btn-quitar').addEventListener('click', () => { fila.remove(); recalcular(); });
    fila.querySelector('.input-cantidad').addEventListener('input', recalcular);
    fila.querySelector('.input-precio').addEventListener('input', recalcular);
    recalcular();
}

document.getElementById('btnAgregarFila').addEventListener('click', agregarFila);

// Agregar una fila inicial
agregarFila();
</script>
@endpush
