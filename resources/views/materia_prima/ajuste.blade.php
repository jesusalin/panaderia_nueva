@extends('layouts.app')
@section('title', 'Ajustar stock')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li>
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.show', $materiaPrima) }}">{{ $materiaPrima->nombre }}</a></li>
    <li class="breadcrumb-item active">Ajustar stock</li>
@endsection

@include('partials.form-secciones')

@push('styles')
<style>
    .aj-tipos { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; margin-bottom: 1.1rem; }
    .aj-tipo { margin: 0; cursor: pointer; }
    .aj-tipo input { position: absolute; opacity: 0; }
    .aj-tipo .caja { display: block; border: 2px solid #e9ecef; border-radius: 12px; padding: .9rem 1rem; height: 100%; transition: .12s; }
    .aj-tipo .caja b { display: block; color: #1a1a2e; }
    .aj-tipo .caja small { color: #8a8a9d; }
    .aj-tipo .caja i { font-size: 1.2rem; color: #b5451b; margin-bottom: .35rem; display: block; }
    .aj-tipo input:checked + .caja { border-color: #b5451b; background: #fffaf7; }
    .aj-tipo input:focus-visible + .caja { box-shadow: 0 0 0 3px rgba(181,69,27,.2); }
    body.dark-mode .aj-tipo .caja { border-color: #33334d; }
    body.dark-mode .aj-tipo .caja b { color: #f0f0f7; }
    body.dark-mode .aj-tipo input:checked + .caja { background: rgba(181,69,27,.12); }
    .aj-motivos { display: flex; flex-wrap: wrap; gap: .4rem; }
    .aj-motivo { margin: 0; cursor: pointer; }
    .aj-motivo input { position: absolute; opacity: 0; }
    .aj-motivo span { display: inline-block; border: 1.5px solid #e9ecef; border-radius: 20px; padding: .35rem .85rem; font-size: .82rem; font-weight: 600; color: #6c757d; }
    .aj-motivo input:checked + span { border-color: #b5451b; background: #b5451b; color: #fff; }
    body.dark-mode .aj-motivo span { border-color: #33334d; color: #b0b0cc; }
    .aj-cant { display: flex; gap: .5rem; }
    .aj-cant input { font-size: 1.3rem; font-weight: 800; height: 50px; }
    .aj-cant select { width: 90px; height: 50px; }
    .aj-resultado { background: #f7f5f3; border-radius: 12px; padding: 1rem; display: flex; align-items: center; justify-content: center; gap: 1rem; font-size: 1.05rem; flex-wrap: wrap; }
    .aj-resultado b { font-size: 1.55rem; color: #1a1a2e; }
    .aj-resultado .dif { font-weight: 800; }
    .aj-resultado .dif.mas { color: #1e8e5a; } .aj-resultado .dif.menos { color: #c0392b; }
    body.dark-mode .aj-resultado { background: #24243b; }
    body.dark-mode .aj-resultado b { color: #f0f0f7; }
    .aj-valor { text-align: center; font-size: .8rem; color: #8a8a9d; margin-top: .5rem; }
    @media (max-width: 576px) { .aj-tipos { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
@php
    $m = $materiaPrima;
    $u = $m->unidad->abreviatura ?? '';
    $sub = \App\Http\Controllers\ProduccionController::subunidad($u);
    $tipo = old('tipo_ajuste', request('tipo', 'merma'));
@endphp
<div class="pfm-hero">
    <div class="ph-foto"><i class="fas fa-balance-scale"></i></div>
    <div><small>Ajustar stock · queda registrado en el kardex</small><h3>{{ $m->nombre }}</h3></div>
    <div class="ph-datos">
        <div><b>{{ \App\Support\Cantidad::conUnidad($m->stock_actual, $u) }}</b><span>según el sistema</span></div>
        <div><b>S/ {{ number_format($m->costo_unitario, 2) }}</b><span>por {{ $u }}</span></div>
    </div>
</div>

<div class="row justify-content-center"><div class="col-lg-8 col-xl-7">
<form action="{{ route('materia-prima.ajuste.store', $m) }}" method="POST" id="formAjuste">
    @csrf
    <div class="pfm-card">
        <div class="pfm-card-body">
            <div class="aj-tipos">
                <label class="aj-tipo">
                    <input type="radio" name="tipo_ajuste" value="merma" {{ $tipo === 'merma' ? 'checked' : '' }}>
                    <span class="caja"><i class="fas fa-trash-alt"></i><b>Merma o pérdida</b><small>Se venció, se rompió, se derramó o se malogró.</small></span>
                </label>
                <label class="aj-tipo">
                    <input type="radio" name="tipo_ajuste" value="conteo" {{ $tipo === 'conteo' ? 'checked' : '' }}>
                    <span class="caja"><i class="fas fa-clipboard-check"></i><b>Corrección por conteo</b><small>Contaste y en el almacén hay otra cantidad.</small></span>
                </label>
            </div>

            <div id="grupoMerma">
                <div class="form-group">
                    <label>¿Qué pasó? <span class="text-danger">*</span></label>
                    <div class="aj-motivos">
                        @foreach(\App\Http\Controllers\MateriaPrimaController::MOTIVOS_MERMA as $clave => $texto)
                            <label class="aj-motivo"><input type="radio" name="motivo_merma" value="{{ $clave }}" {{ old('motivo_merma', 'vencido') === $clave ? 'checked' : '' }}><span>{{ $texto }}</span></label>
                        @endforeach
                    </div>
                    @error('motivo_merma')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>¿Cuánto se perdió? <span class="text-danger">*</span></label>
                    <div class="aj-cant">
                        <input type="number" name="cantidad" id="inCantidad" step="any" min="0" class="form-control @error('cantidad') is-invalid @enderror" value="{{ old('cantidad') }}" placeholder="0">
                    </div>
                    @error('cantidad')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>

            <div id="grupoConteo">
                <div class="form-group">
                    <label>¿Cuánto hay en el almacén? <span class="text-danger">*</span></label>
                    <div class="aj-cant">
                        <input type="number" name="stock_real" id="inReal" step="any" min="0" class="form-control @error('stock_real') is-invalid @enderror" value="{{ old('stock_real') }}" placeholder="0">
                    </div>
                    @error('stock_real')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>Unidad en la que escribes</label>
                <select name="unidad" id="inUnidad" class="form-control" style="max-width:220px;">
                    <option value="base" {{ old('unidad') === 'base' ? 'selected' : '' }}>{{ $u }}</option>
                    @if($sub)<option value="sub" {{ old('unidad', $m->stock_actual < 1 ? 'sub' : '') === 'sub' ? 'selected' : '' }}>{{ $sub }}</option>@endif
                </select>
            </div>

            <div class="form-group">
                <label>Detalle <span id="obsReq" class="text-danger">*</span> <small class="text-muted font-weight-normal" id="obsOpc">(opcional)</small></label>
                <input type="text" name="observacion" maxlength="200" class="form-control @error('observacion') is-invalid @enderror"
                       value="{{ old('observacion') }}" placeholder="Ej: saco mojado por la lluvia">
                @error('observacion')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="aj-resultado">
                <span>{{ \App\Support\Cantidad::conUnidad($m->stock_actual, $u) }}</span><i class="fas fa-arrow-right text-muted"></i>
                <b id="ajDespues">{{ \App\Support\Cantidad::conUnidad($m->stock_actual, $u) }}</b>
                <span class="dif" id="ajDif"></span>
            </div>
            <div class="aj-valor" id="ajValor"></div>

            <button type="submit" class="btn btn-primary pfm-guardar mt-3"><i class="fas fa-check mr-1"></i>Registrar ajuste</button>
            <a href="{{ route('materia-prima.show', $m) }}" class="btn btn-link btn-block btn-sm text-muted">Cancelar</a>
        </div>
    </div>
</form>
</div></div>
@endsection

@push('scripts')
<script>
(function () {
    const antes = {{ (float) $m->stock_actual }}, costo = {{ (float) $m->costo_unitario }}, u = @json($u);
    const form = document.getElementById('formAjuste');
    const $ = id => document.getElementById(id);
    function pintar() {
        const tipo = form.querySelector('[name=tipo_ajuste]:checked').value;
        const merma = tipo === 'merma';
        $('grupoMerma').style.display = merma ? '' : 'none';
        $('grupoConteo').style.display = merma ? 'none' : '';
        const motivo = (form.querySelector('[name=motivo_merma]:checked') || {}).value;
        const obligatorio = !merma || motivo === 'otro';
        $('obsReq').style.display = obligatorio ? '' : 'none';
        $('obsOpc').style.display = obligatorio ? 'none' : '';
        const f = $('inUnidad').value === 'sub' ? 1000 : 1;
        let despues = antes;
        if (merma) despues = antes - (parseFloat($('inCantidad').value) || 0) / f;
        else if ($('inReal').value !== '') despues = (parseFloat($('inReal').value) || 0) / f;
        const dif = +(despues - antes).toFixed(6);
        $('ajDespues').textContent = fmtCantU(Math.max(despues, 0), u);
        $('ajDif').textContent = dif === 0 ? '' : '(' + fmtCantU(dif, u, true) + ')';
        $('ajDif').className = 'dif ' + (dif > 0 ? 'mas' : 'menos');
        $('ajValor').textContent = dif === 0 ? '' : (dif < 0 ? 'Pérdida de ' : 'Aumento de ') + 'S/ ' + Math.abs(dif * costo).toFixed(2) + ' en el valor del almacén' + (despues < 0 ? ' — no puedes descontar más de lo que hay' : '');
    }
    form.addEventListener('input', pintar);
    form.addEventListener('change', pintar);
    pintar();
})();
</script>
@endpush
