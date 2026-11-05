@extends('layouts.app')
@section('title', 'Órdenes automáticas')
@section('breadcrumb')
    @if(auth()->user()->hasPermiso('compras.compras'))<li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>@endif
    <li class="breadcrumb-item active">Órdenes automáticas</li>
@endsection

@include('partials.compras-estilos')

@push('styles')
<style>
    .oa-grupo { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); margin-bottom: 1rem; overflow: hidden; }
    body.dark-mode .oa-grupo { background: #1f1f33; }
    .oa-cab { display: flex; align-items: center; gap: .75rem; padding: .8rem 1.1rem; background: #faf7f4; border-bottom: 1px solid #f1ebe5; flex-wrap: wrap; }
    body.dark-mode .oa-cab { background: #24243b; border-color: #33334d; }
    .oa-cab .oc-nombre { font-weight: 800; color: #1a1a2e; }
    body.dark-mode .oa-cab .oc-nombre { color: #f0f0f7; }
    .oa-cab .oc-sub { font-size: .76rem; color: #8a8a9d; }
    .oa-cab .oc-der { margin-left: auto; display: flex; gap: .6rem; align-items: center; }
    .oa-cab .oc-total { font-weight: 800; color: #b5451b; }
    .oa-cab.sinprov { background: #fdf0ee; }
    body.dark-mode .oa-cab.sinprov { background: rgba(231,76,60,.1); }
    .oa-fila { display: grid; grid-template-columns: 28px minmax(170px, 1.4fr) minmax(150px, 1fr) 190px 110px 40px; gap: .8rem; align-items: center; padding: .75rem 1.1rem; border-top: 1px solid #f5f1ed; }
    .oa-fila:first-of-type { border-top: none; }
    body.dark-mode .oa-fila { border-color: #2c2c44; }
    .oa-fila.desmarcada { opacity: .55; }
    .oa-fila .of-nombre a, .oa-fila .of-nombre b { font-weight: 700; color: #1a1a2e; }
    body.dark-mode .oa-fila .of-nombre a, body.dark-mode .oa-fila .of-nombre b { color: #f0f0f7; }
    .oa-fila .of-nombre small { display: block; font-size: .74rem; color: #8a8a9d; }
    .oa-fila .of-stock small { font-size: .74rem; color: #8a8a9d; display: block; }
    .oa-fila .of-stock .urg { font-weight: 800; font-size: .74rem; }
    .oa-fila .of-cant label { font-size: .64rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; color: #adb5bd; margin: 0; }
    .oa-fila .of-cant .input-group { flex-wrap: nowrap; }
    .oa-fila .of-costo { text-align: right; font-weight: 800; color: #1a1a2e; }
    body.dark-mode .oa-fila .of-costo { color: #f0f0f7; }
    .oa-fila input[type=checkbox] { width: 18px; height: 18px; accent-color: #b5451b; }
    .oa-prov { max-width: 330px; font-size: .78rem; }
    .oa-destino { display: inline-block; margin-top: .3rem; font-size: .74rem; font-weight: 600; color: #1c5d80; background: rgba(52,152,219,.12); border-radius: 10px; padding: .1rem .55rem; }
    body.dark-mode .oa-destino { color: #9fd0f5; }
    .oa-pie-det { font-size: .76rem; opacity: .8; flex: 1 1 200px; min-width: 0; }
    .oa-ya { font-size: .7rem; font-weight: 800; background: rgba(46,204,113,.15); color: #1e8e5a; border-radius: 10px; padding: .05rem .45rem; }
    @media (max-width: 991px) {
        .oa-fila { grid-template-columns: 28px 1fr auto; }
        .oa-fila .of-stock, .oa-fila .of-cant { grid-column: 2 / -1; }
        .oa-fila .of-costo { grid-column: 2; text-align: left; }
    }
    .oa-pie {  z-index: 20; background: #1a1a2e; color: #fff; border-radius: 14px; padding: .8rem 1.1rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; box-shadow: 0 -4px 20px rgba(0,0,0,.15); }
    .oa-pie b { font-size: 1.1rem; }
    .oa-pie small { opacity: .7; display: block; font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; }
    .oa-pie .btn { margin-left: auto; }
    .oa-info { background: #eef6fc; color: #1c5d80; border-radius: 12px; padding: .75rem 1rem; font-size: .84rem; margin-bottom: 1rem; display: flex; gap: .6rem; }
    body.dark-mode .oa-info { background: rgba(52,152,219,.12); color: #9fd0f5; }
    .oa-tabs { display: flex; gap: .5rem; margin-bottom: 1rem; flex-wrap: wrap; }
</style>
@endpush

@section('content')
@php
    $C = \App\Support\Cantidad::class;
    $puedeInsumo = auth()->user()->hasPermiso('inventario.materia_prima');
    $tabs = ['pendiente' => ['Pendientes', 'fa-clock'], 'convertida' => ['Convertidas en compra', 'fa-check'], 'descartada' => ['Descartadas', 'fa-ban'], 'todas' => ['Todas', 'fa-layer-group']];
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-robot mr-2 text-secondary"></i>Órdenes automáticas</h2>
        <p>El sistema revisa el stock cada vez que entras y sugiere qué comprar cuando un insumo llega a su mínimo</p>
    </div>
    <div class="toolbar-actions">
        @if(auth()->user()->hasPermiso('compras.compras'))
            <a href="{{ route('compras.index') }}" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Compras</a>
        @endif
        <form action="{{ route('ordenes-automaticas.generar') }}" method="POST">
            @csrf
            <button class="btn btn-outline-secondary"><i class="fas fa-sync-alt mr-1"></i>Revisar stock ahora</button>
        </form>
    </div>
</div>

@if($nuevas > 0)
    <div class="alert alert-info"><i class="fas fa-bell mr-1"></i>Se detectaron <b>{{ $nuevas }}</b> insumo{{ $nuevas == 1 ? '' : 's' }} que llegaron a su mínimo: ya están en la lista.</div>
@endif

<div class="inv-chips oa-tabs">
    @foreach($tabs as $clave => [$texto, $icono])
        <a href="{{ route('ordenes-automaticas.index', ['estado' => $clave]) }}" class="inv-chip {{ $estado === $clave ? 'activo' : '' }}">
            <i class="fas {{ $icono }} mr-1"></i>{{ $texto }}
            @if($clave !== 'todas')<b class="ml-1">{{ $conteos[$clave] }}</b>@endif
        </a>
    @endforeach
</div>

@if($estado === 'pendiente')
    @if($grupos->isEmpty())
        <div class="empty-state">
            <i class="fas fa-check-circle" style="color:#1e8e5a;opacity:1"></i>
            <p><b>Todo al día.</b> Ningún insumo está en su mínimo sin compra en camino.</p>
        </div>
    @else
        <div class="oa-info">
            <i class="fas fa-info-circle mt-1"></i>
            <div>Revisa la cantidad y <b>a quién pedirlo</b> (puedes elegir otro proveedor si está más barato: ★), desmarca lo que no quieras y pulsa <b>Convertir en compras</b>: se crea <b>una compra por proveedor</b> con todo lo marcado.
                Las compras quedan "por recibir" y el stock sube recién cuando llega el pedido.</div>
        </div>

        <form action="{{ route('ordenes-automaticas.convertir-varias') }}" method="POST" id="formOrdenes" class="js-confirm" data-confirm-title="¿Crear las compras?" data-confirm="">
            @csrf
            @foreach($grupos as $idProv => $grupo)
                @php $prov = $grupo->first()->proveedor; $sinProv = !$idProv || !$prov || $prov->estado !== 'activo'; @endphp
                @if($sinProv)
                    <div class="oa-grupo" data-grupo="0">
                        <div class="oa-cab sinprov">
                            <div class="pv-avatar inactivo"><i class="fas fa-question"></i></div>
                            <div>
                                <div class="oc-nombre text-danger">Sin proveedor{{ $prov ? ' activo' : '' }}</div>
                                <div class="oc-sub">Elige en cada insumo a quién pedírselo (o asígnale un proveedor principal en Materia Prima)</div>
                            </div>
                        </div>
                @else
                    @include('ordenes-automaticas._grupo-cab', ['gid' => $prov->id, 'gnombre' => $prov->nombre, 'gini' => $prov->getIniciales(), 'gtel' => $prov->telefono])
                @endif
                    @foreach($grupo as $o)
                        @php
                            $m = $o->materia; $u = $m->unidad->abreviatura ?? '';
                            $porDia = $consumo[$m->id] ?? null;
                            $dias = $m->diasCobertura($porDia);
                            $repuesto = $m->stock_actual > $m->stock_minimo;
                            $pct = min(100, round($m->stock_actual / max($m->stock_minimo * 2, 0.001) * 100));
                            $pp = ($precios[$m->id] ?? collect())->filter(fn ($x) => $x['activo']);
                            $barato = $pp->min('precio');
                            $elegido = $sinProv ? null : $prov->id;
                            $deEse = $elegido ? $pp->firstWhere('id_proveedor', $elegido) : null;
                            $precioIni = $deEse['precio'] ?? (float) $m->costo_unitario;
                        @endphp
                        <div class="oa-fila" data-costo="{{ $precioIni }}" data-costo-prom="{{ (float) $m->costo_unitario }}" data-u="{{ $u }}">
                            <div>
                                <input type="checkbox" name="ordenes[]" value="{{ $o->id }}" class="oa-check" {{ $repuesto || !$elegido ? '' : 'checked' }} {{ $elegido ? '' : 'disabled' }} aria-label="Pedir {{ $m->nombre }}">
                            </div>
                            <div class="of-nombre">
                                @if($puedeInsumo)<a href="{{ route('materia-prima.show', $m) }}">{{ $m->nombre }}</a>@else<b>{{ $m->nombre }}</b>@endif
                                @if($repuesto)<span class="oa-ya">ya se repuso</span>@endif
                                <small>Orden #{{ $o->id }} · {{ $o->created_at->locale('es')->diffForHumans() }}</small>
                                <select name="proveedores[{{ $o->id }}]" class="form-control form-control-sm oa-prov mt-1" aria-label="Proveedor para {{ $m->nombre }}">
                                    @unless($elegido)<option value="">— Elige a quién pedirlo —</option>@endunless
                                    @if($pp->count())
                                        <optgroup label="Ya le compramos">
                                            @foreach($pp as $x)
                                                @php $pv = $proveedores->firstWhere('id', $x['id_proveedor']); @endphp
                                                @if($pv)
                                                <option value="{{ $pv->id }}" data-precio="{{ $x['precio'] }}" data-nombre="{{ $pv->nombre }}" data-ini="{{ $pv->getIniciales() }}" data-tel="{{ $pv->telefono }}" {{ $elegido == $pv->id ? 'selected' : '' }}>
                                                    {{ $pv->nombre }} · S/ {{ number_format($x['precio'], 2) }}/{{ $u }}{{ $x['precio'] == $barato && $pp->count() > 1 ? ' ★ más barato' : '' }}{{ $pv->id == $m->id_proveedor ? ' (principal)' : '' }}
                                                </option>
                                                @endif
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    <optgroup label="{{ $pp->count() ? 'Otros proveedores' : 'Proveedores' }}">
                                        @foreach($proveedores as $pv)
                                            @unless($pp->contains('id_proveedor', $pv->id))
                                                <option value="{{ $pv->id }}" data-precio="" data-nombre="{{ $pv->nombre }}" data-ini="{{ $pv->getIniciales() }}" data-tel="{{ $pv->telefono }}" {{ $elegido == $pv->id ? 'selected' : '' }}>
                                                    {{ $pv->nombre }}{{ $pv->id == $m->id_proveedor ? ' (principal)' : '' }}
                                                </option>
                                            @endunless
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                            <div class="of-stock">
                                <div class="stock-gauge mb-1" style="max-width:170px">
                                    <div class="sg-track"><div class="sg-fill bajo" style="width: {{ $pct }}%;"></div></div>
                                </div>
                                <small>Hay <b>{{ $C::conUnidad($m->stock_actual, $u) }}</b> · mínimo {{ $C::conUnidad($m->stock_minimo, $u) }}</small>
                                <span class="urg {{ $m->stock_actual <= 0 ? 'text-danger' : ($dias !== null && $dias <= 2 ? 'text-danger' : 'text-muted') }}">
                                    @if($m->stock_actual <= 0) Agotado
                                    @elseif($dias !== null) Alcanza para {{ $dias < 1 ? 'menos de 1 día' : $dias . ' día' . ($dias == 1 ? '' : 's') }}
                                    @else Sin consumo reciente @endif
                                </span>
                            </div>
                            <div class="of-cant">
                                <label for="cant{{ $o->id }}">Cantidad a pedir</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="any" min="0.001" class="form-control oa-cant" id="cant{{ $o->id }}" name="cantidades[{{ $o->id }}]" value="{{ $o->cantidad_sugerida }}">
                                    <div class="input-group-append"><span class="input-group-text">{{ $u }}</span></div>
                                </div>
                                <small class="text-muted oa-queda" style="font-size:.72rem" data-stock="{{ (float) $m->stock_actual }}" data-u="{{ $u }}"></small>
                            </div>
                            <div class="of-costo"><span class="oa-sub">S/ 0.00</span><div class="small text-muted font-weight-normal oa-pu"></div></div>
                            <div>
                                <button type="submit" form="descartar{{ $o->id }}" class="btn btn-sm btn-light text-muted" title="Descartar esta orden" aria-label="Descartar la orden de {{ $m->nombre }}"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="oa-pie barra-fija">
                <div><small>Marcadas</small><b id="pMarcadas">0</b></div>
                <div><small>Compras a crear</small><b id="pCompras">0</b></div>
                <div><small>Costo estimado (con IGV)</small><b id="pTotal">S/ 0.00</b></div>
                <div class="oa-pie-det" id="pDetalle"></div>
                <button type="submit" class="btn btn-success" id="btnConvertir"><i class="fas fa-shopping-cart mr-1"></i>Convertir en compras</button>
            </div>
        </form>


        {{-- Formularios de "descartar" (fuera del formulario principal) --}}
        @foreach($grupos->flatten(1) as $o)
            <form id="descartar{{ $o->id }}" action="{{ route('ordenes-automaticas.descartar', $o) }}" method="POST" class="js-confirm d-none"
                  data-confirm-title="¿Descartar la orden?" data-confirm="No se pedirá {{ $o->materia->nombre }} por ahora. No se volverá a sugerir mientras su stock no siga bajando.">@csrf</form>
        @endforeach
    @endif
@else
    @include('ordenes-automaticas._lista')
@endif
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('formOrdenes');
    if (!form) return;
    const soles = n => 'S/ ' + (Math.round(n * 100) / 100).toFixed(2);

    const esc = t => String(t).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    // Precio de la fila según el proveedor elegido: lo que nos cobró la última vez o, si nunca, el costo promedio
    function precioFila(f) {
        const sel = f.querySelector('.oa-prov'), op = sel.selectedOptions[0];
        const pr = op && op.dataset.precio !== '' && op.dataset.precio !== undefined ? +op.dataset.precio : null;
        f.dataset.costo = pr !== null ? pr : f.dataset.costoProm;
        f.querySelector('.oa-pu').textContent = !sel.value ? 'Elige el proveedor' :
            'S/ ' + (+f.dataset.costo).toFixed(2) + '/' + f.dataset.u + (pr !== null ? ' (le pagamos)' : ' (costo prom.)');
    }

    // Al cambiar el proveedor el insumo NO se mueve de lugar: solo se marca a quién se le pedirá.
    // Las compras se arman según lo elegido en cada fila.
    function marcarDestino(f) {
        const sel = f.querySelector('.oa-prov'), id = sel.value, op = sel.selectedOptions[0];
        const chk = f.querySelector('.oa-check'), grupo = f.closest('.oa-grupo').dataset.grupo;
        const vacia = sel.querySelector('option[value=""]');
        if (id && vacia) vacia.remove();
        if (!id) { chk.disabled = true; chk.checked = false; }
        else if (chk.disabled) { chk.disabled = false; chk.checked = true; }
        let tag = f.querySelector('.oa-destino');
        const otro = id && id !== grupo;
        if (otro && !tag) { tag = document.createElement('span'); tag.className = 'oa-destino'; sel.insertAdjacentElement('afterend', tag); }
        if (tag) { tag.style.display = otro ? '' : 'none'; if (otro) tag.innerHTML = '<i class="fas fa-share mr-1"></i>Se pedirá a <b>' + esc(op.dataset.nombre) + '</b>'; }
    }

    function pintar() {
        let marcadas = 0, total = 0;
        const porProv = {};   // id proveedor => { nombre, n, total }
        form.querySelectorAll('.oa-fila').forEach(f => {
            const chk = f.querySelector('.oa-check'), inp = f.querySelector('.oa-cant'), sel = f.querySelector('.oa-prov');
            const cant = parseFloat(inp.value) || 0, sub = cant * (+f.dataset.costo);
            f.querySelector('.oa-sub').textContent = soles(sub);
            const q = f.querySelector('.oa-queda');
            q.textContent = cant > 0 ? 'quedará ' + fmtCantU(+q.dataset.stock + cant, q.dataset.u) : '';
            const on = chk && chk.checked && !chk.disabled && !!sel.value;
            f.classList.toggle('desmarcada', !on);
            if (on) {
                const op = sel.selectedOptions[0];
                const d = porProv[sel.value] = porProv[sel.value] || { nombre: op.dataset.nombre, n: 0, total: 0 };
                d.n++; d.total += sub; marcadas++; total += sub;
            }
        });
        // Cabecera de cada proveedor: lo que se le va a pedir (venga de su grupo o de otro)
        form.querySelectorAll('.oa-grupo').forEach(g => {
            const d = porProv[g.dataset.grupo] || { n: 0, total: 0 };
            const n = g.querySelector('[data-n-grupo]');
            if (n) n.textContent = d.n ? 'Se le piden ' + d.n + ' insumo' + (d.n === 1 ? '' : 's') : 'No se le pide nada';
            const t = g.querySelector('[data-total-grupo]'); if (t) t.textContent = soles(d.total * 1.18);
            const todas = g.querySelector('.oa-todas');
            if (todas) { const c = [...g.querySelectorAll('.oa-check:not(:disabled)')]; todas.checked = c.length > 0 && c.every(x => x.checked); todas.indeterminate = !todas.checked && c.some(x => x.checked); }
        });
        const lista = Object.values(porProv);
        document.getElementById('pMarcadas').textContent = marcadas;
        document.getElementById('pCompras').textContent = lista.length;
        document.getElementById('pTotal').textContent = soles(total * 1.18);
        document.getElementById('pDetalle').textContent = lista.map(d => d.nombre + ' (' + d.n + ')').join(' · ');
        document.getElementById('btnConvertir').disabled = !marcadas;
        form.dataset.confirm = 'Se crearán ' + lista.length + ' compra(s), una por proveedor: ' + lista.map(d => d.nombre + ' (' + d.n + ' insumo' + (d.n === 1 ? '' : 's') + ', ' + soles(d.total * 1.18) + ')').join('; ')
            + '. Costo estimado: ' + soles(total * 1.18) + '. Quedan por recibir.';
    }
    form.addEventListener('input', pintar);
    form.addEventListener('change', e => {
        if (e.target.classList.contains('oa-todas')) e.target.closest('.oa-grupo').querySelectorAll('.oa-check:not(:disabled)').forEach(c => c.checked = e.target.checked);
        if (e.target.classList.contains('oa-prov')) { const f = e.target.closest('.oa-fila'); precioFila(f); marcarDestino(f); }
        pintar();
    });
    // "Pedir solo a este proveedor": envía solo lo que tiene elegido ese proveedor
    form.addEventListener('click', e => {
        const b = e.target.closest('.oa-solo'); if (!b) return;
        const id = b.closest('.oa-grupo').dataset.grupo;
        const filas = [...form.querySelectorAll('.oa-fila')];
        const suyas = filas.filter(f => f.querySelector('.oa-prov').value === id);
        const algunaMarcada = suyas.some(f => f.querySelector('.oa-check').checked);
        filas.forEach(f => { const c = f.querySelector('.oa-check'); c.checked = suyas.includes(f) && (algunaMarcada ? c.checked : true); });
        pintar();
        if (form.querySelector('.oa-check:checked')) form.requestSubmit ? form.requestSubmit() : form.submit();
    });
    form.querySelectorAll('.oa-fila').forEach(f => { precioFila(f); marcarDestino(f); });
    // Las cantidades de las órdenes desmarcadas no se envían
    form.addEventListener('submit', () => form.querySelectorAll('.oa-fila').forEach(f => {
        const c = f.querySelector('.oa-check'), on = !!(c && c.checked && !c.disabled);
        f.querySelector('.oa-cant').disabled = !on; f.querySelector('.oa-prov').disabled = !on;
    }), true);
    pintar();
})();
</script>
@endpush
