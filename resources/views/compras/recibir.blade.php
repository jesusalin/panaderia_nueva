@extends('layouts.app')
@section('title', 'Recibir compra ' . $compra->codigo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>
    <li class="breadcrumb-item"><a href="{{ route('compras.show', $compra) }}">{{ $compra->codigo }}</a></li>
    <li class="breadcrumb-item active">Recibir</li>
@endsection

@include('partials.form-secciones')
@include('partials.compras-estilos')

@push('styles')
<style>
    .rc-item { display: grid; grid-template-columns: minmax(0, 1.2fr) 200px 130px minmax(0, 1fr); gap: .8rem; align-items: start; padding: .85rem 0; border-top: 1px solid #f3eee9; }
    .rc-item:first-child { border-top: none; }
    body.dark-mode .rc-item { border-color: #2c2c44; }
    .rc-item .ri-nombre b { display: block; color: #1a1a2e; }
    .rc-item .ri-nombre small { color: #8a8a9d; font-size: .76rem; }
    body.dark-mode .rc-item .ri-nombre b { color: #f0f0f7; }
    .rc-item label { font-size: .66rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; color: #adb5bd; margin-bottom: .15rem; }
    .rc-cant { display: flex; gap: .3rem; }
    .rc-cant input { min-width: 0; font-weight: 800; }
    .rc-cant select { width: 70px; flex-shrink: 0; padding-left: .4rem; }
    .rc-atajos { display: flex; gap: .3rem; margin-top: .3rem; }
    .rc-atajos button { border: none; background: #f3eee9; color: #6c757d; font-size: .7rem; font-weight: 700; border-radius: 12px; padding: .12rem .55rem; cursor: pointer; }
    .rc-atajos button:hover { background: #ead9cc; color: #7a2c10; }
    body.dark-mode .rc-atajos button { background: #2c2c44; color: #b0b0cc; }
    .rc-efecto { font-size: .8rem; color: #6c757d; padding-top: 1.35rem; }
    .rc-efecto b { color: #1a1a2e; }
    body.dark-mode .rc-efecto b { color: #f0f0f7; }
    .rc-efecto .dif { display: block; font-weight: 800; font-size: .74rem; margin-top: .15rem; }
    .rc-efecto .dif.menos { color: #c0392b; } .rc-efecto .dif.mas { color: #a5680d; } .rc-efecto .dif.ok { color: #1e8e5a; }
    @media (max-width: 767px) { .rc-item { grid-template-columns: 1fr 1fr; } .rc-item .ri-nombre, .rc-efecto { grid-column: 1 / -1; } .rc-efecto { padding-top: 0; } }
</style>
@endpush

@section('content')
@php $C = \App\Support\Cantidad::class; @endphp

<div class="pfm-hero">
    <div class="ph-foto"><i class="fas fa-box-open"></i></div>
    <div><small>Recibir compra {{ $compra->codigo }}{{ $compra->numero_doc ? ' · ' . $compra->numero_doc : '' }}</small><h3>{{ $compra->proveedor->nombre ?? '—' }}</h3></div>
    <div class="ph-datos">
        <div><b>{{ $compra->fecha_compra->format('d/m/Y') }}</b><span>pedida</span></div>
        <div><b id="heroTotal">S/ {{ number_format($compra->total, 2) }}</b><span>total a pagar</span></div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i>{{ $errors->first() }}</div>
@endif

<form action="{{ route('compras.recibir', $compra) }}" method="POST" id="formRecibir" class="js-confirm" data-confirm-title="¿Recibir el pedido?" data-confirm="">
@csrf @method('PUT')
<div class="pfm-grid">
    <div>
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-clipboard-check"></i></span><h5>Revisa lo que llegó</h5></div>
            <div class="pfm-card-body">
                <p class="small text-muted mb-2">Ya está puesto lo que se pidió. Si llegó otra cantidad o la factura trae otro precio, cámbialo aquí: entra al stock lo que de verdad llegó.</p>
                @foreach($compra->detalles as $d)
                    @php
                        $u = $d->materia->unidad->abreviatura ?? '';
                        $sub = \App\Http\Controllers\ProduccionController::subunidad($u);
                    @endphp
                    <div class="rc-item" data-pedido="{{ $d->cantidad }}" data-stock="{{ (float) $d->materia->stock_actual }}" data-u="{{ $u }}">
                        <div class="ri-nombre">
                            <b>{{ $d->materia->nombre ?? '—' }}</b>
                            <small>Pedido: {{ $C::conUnidad($d->cantidad, $u) }} · hay {{ $C::conUnidad($d->materia->stock_actual, $u) }}</small>
                        </div>
                        <div>
                            <label for="rec{{ $d->id }}">¿Cuánto llegó?</label>
                            <div class="rc-cant">
                                <input type="number" step="any" min="0" class="form-control rc-c" id="rec{{ $d->id }}" name="recibido[{{ $d->id }}]" value="{{ old('recibido.' . $d->id, $d->cantidad) }}">
                                <select class="form-control rc-u" name="unidad[{{ $d->id }}]" {{ $sub ? '' : 'hidden' }} aria-label="Unidad">
                                    <option value="base">{{ $u }}</option>
                                    @if($sub)<option value="sub" {{ old('unidad.' . $d->id) === 'sub' ? 'selected' : '' }}>{{ $sub }}</option>@endif
                                </select>
                                @if(!$sub)<span class="small text-muted align-self-center">{{ $u }}</span>@endif
                            </div>
                            <div class="rc-atajos">
                                <button type="button" data-poner="completo">Llegó completo</button>
                                <button type="button" data-poner="nada">No llegó</button>
                            </div>
                        </div>
                        <div>
                            <label for="pre{{ $d->id }}">Precio por {{ $u }}</label>
                            <div class="pfm-money"><span>S/</span><input type="number" step="0.01" min="0" class="form-control rc-p" id="pre{{ $d->id }}" name="precio[{{ $d->id }}]" value="{{ old('precio.' . $d->id, $d->precio_unitario) }}"></div>
                        </div>
                        <div class="rc-efecto"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="pfm-card">
            <div class="pfm-card-body">
                <label for="nota">Nota de la recepción <span id="notaReq" class="text-danger" style="display:none">* (llegó otra cantidad: cuenta qué pasó)</span></label>
                <input type="text" name="nota_recepcion" id="nota" maxlength="200" class="form-control @error('nota_recepcion') is-invalid @enderror" value="{{ old('nota_recepcion') }}"
                       placeholder="Ej: faltaron 2 sacos, los traen el lunes">
            </div>
        </div>
    </div>

    <div class="pfm-lado">
        <div class="pfm-card">
            <div class="pfm-card-body">
                <h6 class="font-weight-bold mb-2"><i class="fas fa-receipt mr-1 text-muted"></i>Al confirmar</h6>
                <ul class="pfm-check mb-3">
                    <li class="ok"><i class="fas fa-arrow-up"></i><span>El stock de cada insumo sube lo que llegó</span></li>
                    <li class="ok"><i class="fas fa-tag"></i><span>Su costo se recalcula: promedio entre lo que había y lo que llega</span></li>
                    <li class="ok"><i class="fas fa-book"></i><span>Todo queda en el kardex como entrada por compra</span></li>
                </ul>
                <div class="nc-totales" style="font-size:.88rem">
                    <div class="d-flex justify-content-between text-muted"><span>Pedido</span><span>S/ {{ number_format($compra->total, 2) }}</span></div>
                    <div class="d-flex justify-content-between font-weight-bold" style="font-size:1.05rem"><span>A pagar por lo recibido</span><span id="resTotal">S/ {{ number_format($compra->total, 2) }}</span></div>
                    <div class="small text-muted mt-1" id="resDif"></div>
                </div>
                <button type="submit" class="btn btn-success pfm-guardar mt-3"><i class="fas fa-check mr-1"></i>Confirmar recepción</button>
                <a href="{{ route('compras.show', $compra) }}" class="btn btn-link btn-block btn-sm text-muted">Cancelar</a>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('formRecibir');
    const conIgv = {{ $compra->incluye_igv ? 'true' : 'false' }}, total0 = {{ (float) $compra->total }};
    const items = [...form.querySelectorAll('.rc-item')];
    const soles = n => 'S/ ' + (Math.round(n * 100) / 100).toFixed(2);

    function pintar() {
        let suma = 0, difs = 0, nada = 0;
        items.forEach(it => {
            const u = it.dataset.u, pedido = +it.dataset.pedido, stock = +it.dataset.stock;
            const sub = it.querySelector('.rc-u').value === 'sub';
            const v = it.querySelector('.rc-c').value;
            const llego = v === '' ? pedido : (parseFloat(v) || 0) / (sub ? 1000 : 1);
            const precio = parseFloat(it.querySelector('.rc-p').value) || 0;
            suma += Math.round(llego * precio * 100) / 100;
            const dif = +(llego - pedido).toFixed(3);
            if (Math.abs(dif) > 0.0005) difs++;
            if (llego <= 0) nada++;
            it.querySelector('.rc-efecto').innerHTML = (llego > 0 ? 'Stock: ' + fmtCantU(stock, u) + ' → <b>' + fmtCantU(stock + llego, u) + '</b>' : 'No entra nada al stock')
                + '<span class="dif ' + (Math.abs(dif) <= 0.0005 ? 'ok' : (dif < 0 ? 'menos' : 'mas')) + '">'
                + (Math.abs(dif) <= 0.0005 ? '✓ Llegó completo' : (dif < 0 ? 'Faltó ' + fmtCantU(-dif, u) : 'Sobró ' + fmtCantU(dif, u))) + '</span>';
        });
        const total = conIgv ? suma : Math.round(suma * 1.18 * 100) / 100;
        document.getElementById('resTotal').textContent = soles(total);
        document.getElementById('heroTotal').textContent = soles(total);
        const d = total - total0;
        document.getElementById('resDif').textContent = Math.abs(d) < 0.005 ? 'Igual a lo pedido.' : (d < 0 ? 'S/ ' + Math.abs(d).toFixed(2) + ' menos que lo pedido.' : 'S/ ' + d.toFixed(2) + ' más que lo pedido.');
        document.getElementById('notaReq').style.display = difs ? '' : 'none';
        document.getElementById('nota').required = difs > 0;
        form.dataset.confirm = (difs ? difs + ' insumo(s) llegaron con otra cantidad. ' : 'Todo llegó completo. ') + 'El stock va a subir y el total a pagar queda en ' + soles(total) + '.'
            + (nada === items.length ? ' (No llegó nada: mejor anula la compra.)' : '');
    }
    form.addEventListener('input', pintar);
    form.addEventListener('change', pintar);
    form.addEventListener('click', e => {
        const b = e.target.closest('[data-poner]'); if (!b) return;
        const it = b.closest('.rc-item');
        it.querySelector('.rc-u').value = 'base';
        it.querySelector('.rc-c').value = b.dataset.poner === 'completo' ? it.dataset.pedido : 0;
        pintar();
    });
    pintar();
})();
</script>
@endpush
