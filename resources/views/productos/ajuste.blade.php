@extends('layouts.app')
@section('title', 'Ajustar stock')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('productos.show', $producto) }}">{{ $producto->nombre }}</a></li>
    <li class="breadcrumb-item active">Ajustar stock</li>
@endsection

@include('productos.partials._styles')

@push('styles')
<style>
    .aj-tipos { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; margin-bottom: 1.2rem; }
    .aj-tipo { margin: 0; cursor: pointer; }
    .aj-tipo input { position: absolute; opacity: 0; }
    .aj-tipo .caja { display: block; border: 2px solid #e9ecef; border-radius: 12px; padding: .9rem 1rem; height: 100%; transition: .12s; }
    .aj-tipo .caja b { display: block; color: #1a1a2e; font-size: .95rem; }
    .aj-tipo .caja small { color: #8a8a9d; }
    .aj-tipo .caja i { font-size: 1.2rem; color: #b5451b; margin-bottom: .35rem; display: block; }
    .aj-tipo input:checked + .caja { border-color: #b5451b; background: #fffaf7; }
    .aj-tipo input:focus-visible + .caja { box-shadow: 0 0 0 3px rgba(181,69,27,.2); }
    .aj-resultado { background: #f7f5f3; border-radius: 12px; padding: .9rem 1.1rem; display: flex; align-items: center; justify-content: center; gap: 1rem; font-size: 1.05rem; }
    .aj-resultado b { font-size: 1.5rem; color: #1a1a2e; }
    .aj-resultado .dif { font-weight: 800; }
    .aj-resultado .dif.mas { color: #1e8e5a; } .aj-resultado .dif.menos { color: #c0392b; }
    body.dark-mode .aj-tipo .caja { border-color: #33334d; }
    body.dark-mode .aj-tipo .caja b, body.dark-mode .aj-resultado b { color: #f0f0f7; }
    body.dark-mode .aj-tipo input:checked + .caja { background: rgba(181,69,27,.12); }
    body.dark-mode .aj-resultado { background: #24243b; }
    @media (max-width: 576px) { .aj-tipos { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@php $tipo = old('tipo_ajuste', 'merma'); @endphp
<div class="row justify-content-center"><div class="col-lg-7">
<div class="card prod-form-card">
    <div class="prod-form-header">
        <div class="prod-form-icon"><i class="fas fa-balance-scale"></i></div>
        <div>
            <h5>Ajustar stock: {{ $producto->nombre }}</h5>
            <p>En el sistema hay <strong>{{ $producto->stock_actual }}</strong> unidad(es). El ajuste queda registrado en el kardex.</p>
        </div>
    </div>
    <div class="prod-form-body">
        <form action="{{ route('productos.ajuste.store', $producto) }}" method="POST" id="formAjuste">
            @csrf
            <div class="aj-tipos">
                <label class="aj-tipo">
                    <input type="radio" name="tipo_ajuste" value="merma" {{ $tipo === 'merma' ? 'checked' : '' }}>
                    <span class="caja"><i class="fas fa-trash-alt"></i><b>Merma o pérdida</b><small>Se quemó, se cayó, se venció o se regaló.</small></span>
                </label>
                <label class="aj-tipo">
                    <input type="radio" name="tipo_ajuste" value="conteo" {{ $tipo === 'conteo' ? 'checked' : '' }}>
                    <span class="caja"><i class="fas fa-clipboard-check"></i><b>Corrección por conteo</b><small>Contaste y hay otra cantidad.</small></span>
                </label>
            </div>

            <div class="form-group" id="grupoMerma">
                <label>¿Cuántas unidades se perdieron? <span class="text-danger">*</span></label>
                <input type="number" name="cantidad" id="inCantidad" min="1" max="{{ $producto->stock_actual }}" class="form-control @error('cantidad') is-invalid @enderror" value="{{ old('cantidad') }}">
                @error('cantidad')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group" id="grupoConteo">
                <label>¿Cuántas unidades contaste? <span class="text-danger">*</span></label>
                <input type="number" name="stock_real" id="inReal" min="0" class="form-control @error('stock_real') is-invalid @enderror" value="{{ old('stock_real') }}">
                @error('stock_real')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label>Motivo <span class="text-danger">*</span></label>
                <input type="text" name="observacion" maxlength="200" class="form-control @error('observacion') is-invalid @enderror"
                    value="{{ old('observacion') }}" placeholder="Ej: se quemó una bandeja en el horno" required>
                @error('observacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="aj-resultado">
                <span>{{ $producto->stock_actual }}</span><i class="fas fa-arrow-right text-muted"></i>
                <b id="ajDespues">{{ $producto->stock_actual }}</b><span class="text-muted">uds</span>
                <span class="dif" id="ajDif"></span>
            </div>

            <div class="form-actions">
                <a href="{{ route('productos.show', $producto) }}" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i>Registrar ajuste</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection

@push('scripts')
<script>
(function () {
    var antes = {{ (int) $producto->stock_actual }};
    var form = document.getElementById('formAjuste');
    function pintar() {
        var tipo = form.querySelector('[name=tipo_ajuste]:checked').value;
        document.getElementById('grupoMerma').style.display  = tipo === 'merma'  ? '' : 'none';
        document.getElementById('grupoConteo').style.display = tipo === 'conteo' ? '' : 'none';
        document.getElementById('inCantidad').required = tipo === 'merma';
        document.getElementById('inReal').required     = tipo === 'conteo';
        var despues = antes;
        if (tipo === 'merma') despues = antes - (parseInt(document.getElementById('inCantidad').value) || 0);
        else if (document.getElementById('inReal').value !== '') despues = parseInt(document.getElementById('inReal').value);
        var dif = despues - antes, el = document.getElementById('ajDif');
        document.getElementById('ajDespues').textContent = despues;
        el.textContent = dif === 0 ? '' : '(' + (dif > 0 ? '+' : '') + dif + ')';
        el.className = 'dif ' + (dif > 0 ? 'mas' : 'menos');
    }
    form.addEventListener('input', pintar);
    form.addEventListener('change', pintar);
    pintar();
})();
</script>
@endpush
