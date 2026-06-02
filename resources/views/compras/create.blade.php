@extends('layouts.app')
@section('title', 'Nueva Compra')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@section('content')
<form action="{{ route('compras.store') }}" method="POST" id="formCompra">
@csrf
<div class="row">
    {{-- Cabecera --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-shopping-cart mr-2 text-warning"></i>Datos de la Compra</h5>
            </div>
            <div class="card-body">
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
                    <div class="col-md-12 form-group">
                        <label>Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="1">{{ old('observaciones') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detalle --}}
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list mr-2"></i>Ingredientes Comprados</h5>
                <button type="button" class="btn btn-sm btn-success" id="btnAgregarFila">
                    <i class="fas fa-plus mr-1"></i>Agregar fila
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" id="tablaDetalle">
                    <thead class="bg-light">
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
            </div>
            <div class="card-footer text-right">
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-sm mb-0">
                            <tr><td>Subtotal:</td><td class="text-right" id="resSubtotal">S/ 0.00</td></tr>
                            <tr class="text-muted"><td>IGV (18%):</td><td class="text-right" id="resIgv">S/ 0.00</td></tr>
                            <tr class="font-weight-bold h6"><td>Total:</td><td class="text-right text-warning" id="resTotal">S/ 0.00</td></tr>
                        </table>
                    </div>
                </div>
                <div class="mt-3">
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
                    <option value="{{ $m->id }}">{{ $m->nombre }} ({{ $m->unidad->abreviatura }})</option>
                @endforeach
            </select>
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
            <button type="button" class="btn btn-sm btn-outline-danger btn-quitar">
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
    document.querySelectorAll('.fila-detalle').forEach(fila => {
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
    document.getElementById('btnGuardar').disabled = document.querySelectorAll('.fila-detalle').length === 0;
}

function agregarFila() {
    const template = document.getElementById('filaTemplate').innerHTML.replace(/__INDEX__/g, index++);
    const tbody = document.getElementById('detalleBody');
    const tr = document.createElement('tr');
    tr.outerHTML; // just for parsing
    tbody.insertAdjacentHTML('beforeend', template);
    const fila = tbody.lastElementChild;
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
