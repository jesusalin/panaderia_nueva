@extends('layouts.app')
@section('title', 'Nueva compra')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@include('partials.form-secciones')
@include('partials.compras-estilos')

@push('styles')
<style>
    .nc-prov-info { display: flex; flex-wrap: wrap; gap: .4rem .9rem; align-items: center; font-size: .8rem; color: #6c757d; margin-top: .45rem; min-height: 1.2rem; }
    .nc-rapidos { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .65rem; }
    .nc-rapido { border: 1px dashed #d4a98f; background: #fffaf7; color: #a33d17; border-radius: 20px; padding: .3rem .85rem; font-size: .8rem; font-weight: 700; cursor: pointer; }
    .nc-rapido:hover { background: #fdeee5; }
    .nc-rapido[disabled] { opacity: .45; cursor: default; }
    body.dark-mode .nc-rapido { background: rgba(181,69,27,.1); color: #f0a07a; border-color: #6d3a22; }

    .nc-buscar { position: relative; }
    .nc-buscar .sug-lista { width: 100%; }
    .sug-item .nc-tag { font-size: .66rem; font-weight: 800; border-radius: 10px; padding: .05rem .4rem; margin-left: .35rem; }
    .nc-tag.bajo { background: rgba(231,76,60,.13); color: #c0392b; }
    .nc-tag.suyo { background: rgba(181,69,27,.12); color: #b5451b; }
    .nc-tag.ya { background: #eef0f3; color: #6c757d; }

    .nc-items { margin-top: .9rem; }
    .nc-item { display: grid; grid-template-columns: minmax(170px, 1.5fr) 190px 160px 110px 36px; gap: .7rem; align-items: start; padding: .75rem 0; border-top: 1px solid #f3eee9; }
    .nc-item:first-child { border-top: none; }
    body.dark-mode .nc-item { border-color: #2c2c44; }
    .nc-item .ni-nombre b { display: block; color: #1a1a2e; font-size: .92rem; }
    .nc-item .ni-nombre small { color: #8a8a9d; font-size: .75rem; }
    body.dark-mode .nc-item .ni-nombre b { color: #f0f0f7; }
    .nc-item label { font-size: .66rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; color: #adb5bd; margin-bottom: .15rem; }
    .nc-item .ni-cant { display: flex; gap: .3rem; }
    .nc-item .ni-cant input { min-width: 0; }
    .nc-item .ni-cant select { width: 72px; flex-shrink: 0; padding-left: .4rem; }
    .nc-item .ni-hint { display: block; font-size: .72rem; color: #8a8a9d; margin-top: .2rem; min-height: 1em; }
    .nc-item .ni-sub { text-align: right; font-weight: 800; color: #1a1a2e; padding-top: 1.55rem; }
    body.dark-mode .nc-item .ni-sub { color: #f0f0f7; }
    .nc-item .ni-quitar { margin-top: 1.4rem; }
    .nc-item.nuevo { animation: ncDestello 1.2s ease; }
    @keyframes ncDestello { from { background: #fff4e9; } to { background: transparent; } }
    @media (max-width: 767px) {
        .nc-item { grid-template-columns: 1fr 1fr; }
        .nc-item .ni-nombre { grid-column: 1 / -1; }
        .nc-item .ni-sub { padding-top: 0; text-align: left; }
        .nc-item .ni-quitar { margin-top: 0; justify-self: end; }
    }
    .nc-vacio { text-align: center; color: #adb5bd; padding: 1.6rem .5rem .6rem; }
    .nc-vacio i { font-size: 1.8rem; opacity: .5; display: block; margin-bottom: .4rem; }

    .nc-totales { font-size: .88rem; }
    .nc-totales div { display: flex; justify-content: space-between; padding: .2rem 0; color: #6c757d; }
    .nc-totales .tt { border-top: 1px solid #eee5dd; margin-top: .3rem; padding-top: .55rem; color: #1a1a2e; font-weight: 800; align-items: baseline; }
    .nc-totales .tt b { font-size: 1.45rem; color: #b5451b; }
    body.dark-mode .nc-totales .tt { color: #f0f0f7; border-color: #33334d; }
    .nc-recibir { display: flex; gap: .55rem; align-items: flex-start; background: #f2fbf6; border: 1.5px solid #c9eed8; border-radius: 12px; padding: .65rem .8rem; margin: .9rem 0 .7rem; cursor: pointer; font-size: .82rem; }
    .nc-recibir input { margin-top: .2rem; }
    .nc-recibir b { color: #1e8e5a; display: block; }
    body.dark-mode .nc-recibir { background: rgba(46,204,113,.08); border-color: #1e5c3d; }
</style>
@endpush

@section('content')
@php
    // Si volvió con errores, se rearman las filas con lo que escribió
    $itemsIniciales = old('materias') ? collect(old('materias'))->map(fn ($i) => [
        'id' => (int) ($i['id_materia'] ?? 0), 'cant' => $i['cantidad'] ?? '', 'unidad' => $i['unidad'] ?? 'base', 'precio' => $i['precio_unitario'] ?? '',
    ])->values() : collect($inicial['items']);
@endphp

<div class="pfm-hero">
    <div class="ph-foto"><i class="fas fa-shopping-cart"></i></div>
    <div><small>Nueva compra</small><h3>Pedido de insumos a un proveedor</h3></div>
    <div class="ph-datos">
        <div><b id="heroItems">0</b><span>insumos</span></div>
        <div><b id="heroTotal">S/ 0.00</b><span>total con IGV</span></div>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle mr-1"></i><b>Revisa la compra:</b>
        <ul class="mb-0 mt-1 pl-3">@foreach(collect($errors->all())->unique() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form action="{{ route('compras.store') }}" method="POST" id="formCompra">
@csrf
<div class="pfm-grid">
    <div>
        {{-- 1. Proveedor --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-truck"></i></span><h5>¿A quién le compras?</h5></div>
            <div class="pfm-card-body">
                <div class="row">
                    <div class="col-md-6 form-group mb-2">
                        <label>Proveedor <span class="text-danger">*</span></label>
                        <select name="id_proveedor" id="inProveedor" class="form-control @error('id_proveedor') is-invalid @enderror" required>
                            <option value="">— Elige el proveedor —</option>
                            @foreach($proveedores as $p)
                                <option value="{{ $p->id }}" {{ (string) old('id_proveedor', $inicial['proveedor']) === (string) $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 col-6 form-group mb-2">
                        <label>Fecha <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_compra" class="form-control @error('fecha_compra') is-invalid @enderror" max="{{ date('Y-m-d') }}" value="{{ old('fecha_compra', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-3 col-6 form-group mb-2">
                        <label>Factura / boleta</label>
                        <input type="text" name="numero_doc" maxlength="50" class="form-control @error('numero_doc') is-invalid @enderror" value="{{ old('numero_doc') }}" placeholder="F001-000123">
                    </div>
                </div>
                <div class="nc-prov-info" id="provInfo"></div>
                <div class="nc-rapidos">
                    <button type="button" class="nc-rapido" id="btnBajos" disabled><i class="fas fa-exclamation-triangle mr-1"></i><span>Sus insumos con stock bajo</span></button>
                    <button type="button" class="nc-rapido" id="btnRepetir" disabled><i class="fas fa-redo mr-1"></i><span>Repetir su última compra</span></button>
                </div>
            </div>
        </div>

        {{-- 2. Insumos --}}
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-wheat-awn"></i></span><h5>¿Qué insumos?</h5></div>
            <div class="pfm-card-body">
                <div class="search-box nc-buscar">
                    <i class="fas fa-search"></i>
                    <input type="text" id="ncBuscar" class="form-control" autocomplete="off" placeholder="Busca un insumo para agregarlo… (Enter lo agrega)"
                           role="combobox" aria-autocomplete="list" aria-controls="ncSug" aria-label="Buscar insumo para agregar">
                    <div class="sug-lista" id="ncSug" role="listbox"></div>
                </div>
                <div class="pfm-switch mt-3" style="padding:.55rem .8rem">
                    <div class="sw-txt">
                        <b>Los precios incluyen IGV</b>
                        <span>Actívalo si copias los precios de una boleta o factura que ya trae el IGV sumado.</span>
                    </div>
                    <input type="hidden" name="incluye_igv" value="0">
                    <label class="pfm-toggle"><input type="checkbox" name="incluye_igv" id="swIgv" value="1" {{ old('incluye_igv', $inicial['igv']) ? 'checked' : '' }} aria-label="Los precios incluyen IGV"><span></span></label>
                </div>

                <div class="nc-items" id="ncItems"></div>
                <div class="nc-vacio" id="ncVacio"><i class="fas fa-inbox"></i>Busca arriba los insumos que vas a comprar o usa los botones del proveedor.</div>
            </div>
        </div>

        {{-- 3. Nota --}}
        <div class="pfm-card">
            <div class="pfm-card-body">
                <label>Observaciones <small class="text-muted font-weight-normal">(opcional)</small></label>
                <textarea name="observaciones" class="form-control" rows="2" maxlength="500" placeholder="Ej: entregar antes de las 7 a.m.">{{ old('observaciones') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Lado: resumen y guardar --}}
    <div class="pfm-lado">
        <div class="pfm-card">
            <div class="pfm-card-body">
                <h6 class="font-weight-bold mb-2"><i class="fas fa-receipt mr-1 text-muted"></i>Resumen</h6>
                <div class="nc-totales">
                    <div><span id="resItems">0 insumos</span><span></span></div>
                    <div><span>Subtotal</span><span id="resSubtotal">S/ 0.00</span></div>
                    <div><span>IGV (18 %)</span><span id="resIgv">S/ 0.00</span></div>
                    <div class="tt"><span>Total</span><b id="resTotal">S/ 0.00</b></div>
                </div>
                <label class="nc-recibir">
                    <input type="hidden" name="recibir_ahora" value="0">
                    <input type="checkbox" name="recibir_ahora" value="1" {{ old('recibir_ahora') ? 'checked' : '' }}>
                    <span><b>El pedido ya llegó</b>Recibirlo al guardar: el stock sube de una vez (por ejemplo, si compraste en el mercado).</span>
                </label>
                <ul class="pfm-check mb-2" id="checklist">
                    <li data-chk="prov"><i class="fas fa-circle"></i><span>Proveedor elegido</span></li>
                    <li data-chk="items"><i class="fas fa-circle"></i><span>Al menos un insumo</span></li>
                    <li data-chk="datos"><i class="fas fa-circle"></i><span>Cantidades y precios completos</span></li>
                </ul>
                <button type="submit" class="btn btn-primary pfm-guardar" id="btnGuardar" disabled><i class="fas fa-save mr-1"></i>Registrar compra</button>
                <a href="{{ route('compras.index') }}" class="btn btn-link btn-block btn-sm text-muted">Cancelar</a>
            </div>
        </div>
    </div>
</div>
</form>

<script type="application/json" id="ncInsumos">@json($datosInsumos)</script>
<script type="application/json" id="ncUltimas">@json($ultimas)</script>
<script type="application/json" id="ncProveedores">@json($datosProveedores)</script>
<script type="application/json" id="ncIniciales">@json($itemsIniciales)</script>
@endsection

@push('scripts')
<script>
(function () {
    const $ = id => document.getElementById(id);
    const leer = id => JSON.parse($(id).textContent || 'null');
    const insumos = leer('ncInsumos'), ultimas = leer('ncUltimas') || {}, provs = leer('ncProveedores') || {};
    const porId = {}; insumos.forEach(x => porId[x.id] = x);
    const norm = t => (t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const esc = t => String(t).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const soles = n => 'S/ ' + (Math.round(n * 100) / 100).toFixed(2);
    const IGV = 0.18;
    let idx = 0;

    const prov = () => $('inProveedor').value;
    // Precio que se propone: el último que cobró este proveedor; si nunca le compramos, el costo promedio
    const precioDe = x => (x.pp && x.pp[prov()] !== undefined) ? x.pp[prov()] : x.costo;
    const filas = () => [...document.querySelectorAll('.nc-item')];
    const enLista = id => filas().find(f => f.dataset.id === String(id));

    // ── Filas ──
    function agregar(id, cant, precio, unidad, silencioso) {
        const x = porId[id]; if (!x) return;
        const ya = enLista(id);
        if (ya) {   // ya está: se suma a la misma fila
            if (cant) { const inp = ya.querySelector('.ni-c'); inp.value = (+(parseFloat(inp.value) || 0) + +cant).toString(); }
            ya.classList.remove('nuevo'); void ya.offsetWidth; ya.classList.add('nuevo');
            if (!silencioso) ya.querySelector('.ni-c').focus();
            calcular(); return;
        }
        const i = idx++;
        const div = document.createElement('div');
        div.className = 'nc-item nuevo'; div.dataset.id = x.id;
        div.innerHTML =
            '<div class="ni-nombre"><input type="hidden" name="materias[' + i + '][id_materia]" value="' + x.id + '">'
            + '<b>' + esc(x.n) + '</b><small>Hay ' + fmtCantU(x.stock, x.u) + ' · mínimo ' + fmtCantU(x.min, x.u) + (x.bajo ? ' · <span class="text-danger font-weight-bold">stock bajo</span>' : '') + '</small></div>'
            + '<div><label>Cantidad</label><div class="ni-cant"><input type="number" step="any" min="0" class="form-control ni-c" name="materias[' + i + '][cantidad]" value="' + (cant ?? '') + '" placeholder="0" required>'
            + '<select class="form-control ni-u" name="materias[' + i + '][unidad]"' + (x.sub ? '' : ' hidden') + '><option value="base">' + esc(x.u) + '</option>' + (x.sub ? '<option value="sub"' + (unidad === 'sub' ? ' selected' : '') + '>' + esc(x.sub) + '</option>' : '') + '</select>'
            + (x.sub ? '' : '<span class="small text-muted align-self-center">' + esc(x.u) + '</span>') + '</div><span class="ni-hint ni-hc"></span></div>'
            + '<div><label>Precio por ' + esc(x.u) + '</label><div class="pfm-money"><span>S/</span><input type="number" step="0.01" min="0" class="form-control ni-p" name="materias[' + i + '][precio_unitario]" value="' + (precio ?? '') + '" placeholder="0.00" required></div><span class="ni-hint ni-hp"></span></div>'
            + '<div class="ni-sub">S/ 0.00</div>'
            + '<button type="button" class="btn btn-sm btn-light text-danger ni-quitar" title="Quitar" aria-label="Quitar ' + esc(x.n) + '"><i class="fas fa-times"></i></button>';
        $('ncItems').appendChild(div);
        if (!silencioso) (cant ? div.querySelector('.ni-p') : div.querySelector('.ni-c')).focus();
        calcular();
    }

    function calcular() {
        const conIgv = $('swIgv').checked;
        let suma = 0, completos = true;
        filas().forEach(f => {
            const x = porId[f.dataset.id];
            const c = parseFloat(f.querySelector('.ni-c').value) || 0, sub = f.querySelector('.ni-u').value === 'sub';
            const cant = sub ? c / 1000 : c;
            const p = parseFloat(f.querySelector('.ni-p').value);
            const s = cant * (isNaN(p) ? 0 : p);
            suma += Math.round(s * 100) / 100;
            if (!(cant > 0) || isNaN(p)) completos = false;
            f.querySelector('.ni-sub').textContent = soles(s);
            // Cantidad: cómo queda el stock y equivalencia
            const desp = x.stock + cant;
            f.querySelector('.ni-hc').textContent = cant > 0 ? (sub ? '= ' + fmtCantU(cant, x.u) + ' · ' : '') + 'quedará ' + fmtCantU(desp, x.u) : '';
            // Precio: comparado con el costo actual (sin IGV)
            const hp = f.querySelector('.ni-hp');
            // Comparado con lo último que cobró este proveedor (o con el costo promedio si es la primera vez)
            const ref = precioDe(x), deEste = x.pp && x.pp[prov()] !== undefined;
            let html = '';
            if (!isNaN(p) && ref > 0) {
                const neto = conIgv ? p / (1 + IGV) : p;
                const v = Math.round((neto - ref) / ref * 100);
                html = (deEste ? 'Le pagamos: S/ ' : 'Costo prom.: S/ ') + ref.toFixed(2) + (Math.abs(v) >= 1 ? ' <span class="cp-var ' + (v > 0 ? 'sube' : 'baja') + '">' + (v > 0 ? '▲ ' : '▼ ') + Math.abs(v) + '%</span>' : ' <span class="cp-var igual">igual</span>');
            } else if (ref > 0) html = (deEste ? 'Le pagamos: S/ ' : 'Costo prom.: S/ ') + ref.toFixed(2);
            // ¿Otro proveedor lo vende más barato?
            const otros = Object.entries(x.pp || {}).filter(([id]) => id !== prov() && provs[id]).sort((a, b) => a[1] - b[1]);
            if (otros.length && otros[0][1] < ref * 0.98) html += '<br><span class="text-success">Más barato en ' + esc(provs[otros[0][0]].nombre) + ': S/ ' + otros[0][1].toFixed(2) + '</span>';
            hp.innerHTML = html;
        });
        suma = Math.round(suma * 100) / 100;
        let subtotal, igv, total;
        if (conIgv) { total = suma; subtotal = Math.round(suma / (1 + IGV) * 100) / 100; igv = Math.round((total - subtotal) * 100) / 100; }
        else { subtotal = suma; igv = Math.round(suma * IGV * 100) / 100; total = subtotal + igv; }
        const n = filas().length;
        $('resItems').textContent = n + ' insumo' + (n === 1 ? '' : 's');
        $('resSubtotal').textContent = soles(subtotal); $('resIgv').textContent = soles(igv); $('resTotal').textContent = soles(total);
        $('heroItems').textContent = n; $('heroTotal').textContent = soles(total);
        $('ncVacio').style.display = n ? 'none' : '';
        const marcar = (k, ok) => { const li = document.querySelector('[data-chk=' + k + ']'); li.className = ok ? 'ok' : 'no'; li.querySelector('i').className = 'fas ' + (ok ? 'fa-check-circle' : 'fa-circle'); };
        marcar('prov', !!prov()); marcar('items', n > 0); marcar('datos', n > 0 && completos);
        $('btnGuardar').disabled = !(prov() && n > 0 && completos);
        pintarBuscador(false);
    }

    $('ncItems').addEventListener('input', calcular);
    $('ncItems').addEventListener('change', calcular);
    $('ncItems').addEventListener('click', e => { const b = e.target.closest('.ni-quitar'); if (b) { b.closest('.nc-item').remove(); calcular(); } });
    $('swIgv').addEventListener('change', calcular);

    // ── Proveedor: datos y botones rápidos ──
    function pintarProveedor() {
        const p = prov(), d = provs[p];
        const bajos = insumos.filter(x => String(x.prov) === p && x.bajo);
        const ult = ultimas[p];
        $('provInfo').innerHTML = !p ? '' : [
            d && d.contacto ? '<span><i class="fas fa-user mr-1"></i>' + esc(d.contacto) + '</span>' : '',
            d && d.tel ? '<span><i class="fas fa-phone mr-1"></i>' + esc(d.tel) + '</span>' : '',
            '<span><i class="fas fa-wheat-awn mr-1"></i>' + (d ? d.insumos : 0) + ' insumo(s) suyos</span>',
            ult ? '<span><i class="fas fa-history mr-1"></i>Última compra: ' + esc(ult.fecha) + '</span>' : '<span>Primera compra a este proveedor</span>',
        ].join('');
        $('btnBajos').disabled = !bajos.length;
        $('btnBajos').querySelector('span').textContent = bajos.length ? 'Agregar sus ' + bajos.length + ' insumo(s) con stock bajo' : 'Ninguno de sus insumos está bajo';
        $('btnRepetir').disabled = !ult;
        $('btnRepetir').querySelector('span').textContent = ult ? 'Repetir su última compra (' + ult.fecha + ')' : 'Sin compras anteriores';
        calcular();
    }
    $('inProveedor').addEventListener('change', pintarProveedor);
    $('btnBajos').addEventListener('click', () => {
        insumos.filter(x => String(x.prov) === prov() && x.bajo).forEach(x => agregar(x.id, x.repo ? +x.repo.toFixed(3) : '', precioDe(x), 'base', true));
    });
    $('btnRepetir').addEventListener('click', () => {
        const u = ultimas[prov()]; if (!u) return;
        if (u.igv !== $('swIgv').checked) { $('swIgv').checked = u.igv; }
        u.items.forEach(it => agregar(it.id, it.cant, it.precio, 'base', true));
    });

    // ── Buscador con lista de sugerencias ──
    const buscar = $('ncBuscar'), sug = $('ncSug');
    let vis = [], activo = -1;
    function marcarHtml(txt, palabras) {
        const n = norm(txt), m = new Array(txt.length).fill(false);
        palabras.forEach(p => { let i = n.indexOf(p); while (p && i !== -1) { for (let k = i; k < i + p.length; k++) m[k] = true; i = n.indexOf(p, i + 1); } });
        let h = '', a = false;
        for (let k = 0; k < txt.length; k++) { if (m[k] && !a) { h += '<mark>'; a = true; } if (!m[k] && a) { h += '</mark>'; a = false; } h += esc(txt[k]); }
        return h + (a ? '</mark>' : '');
    }
    function pintarBuscador(abrir) {
        if (!abrir && !sug.classList.contains('abierta')) return;
        const palabras = norm(buscar.value).split(/\s+/).filter(Boolean), p = prov();
        vis = insumos.filter(x => palabras.every(w => norm(x.n).includes(w)))
            // primero los del proveedor elegido, y entre ellos los de stock bajo
            .sort((a, b) => (String(b.prov) === p) - (String(a.prov) === p) || b.bajo - a.bajo || a.n.localeCompare(b.n));
        activo = -1;
        let h = '<div class="sug-cab"><span>' + (palabras.length ? 'Coincidencias' : (p ? 'Primero los de este proveedor' : 'Todos los insumos')) + '</span><span>' + vis.length + ' de ' + insumos.length + '</span></div>';
        if (!vis.length) h += '<div class="sug-vacio"><i class="fas fa-search mr-1"></i>Nada coincide con "' + esc(buscar.value) + '"</div>';
        else h += '<div class="sug-items">' + vis.slice(0, 80).map((x, i) => '<div class="sug-item" role="option" data-i="' + i + '"><span class="si-ic"><i class="fas fa-wheat-awn"></i></span><span><span class="si-t d-block">'
            + marcarHtml(x.n, palabras) + (x.bajo ? '<span class="nc-tag bajo">stock bajo</span>' : '') + (p && String(x.prov) === p ? '<span class="nc-tag suyo">de este proveedor</span>' : '') + (enLista(x.id) ? '<span class="nc-tag ya">ya agregado</span>' : '')
            + '</span><span class="si-s">Hay ' + esc(fmtCantU(x.stock, x.u)) + ' · S/ ' + precioDe(x).toFixed(2) + ' por ' + esc(x.u) + (x.pp && x.pp[p] !== undefined ? ' (lo que cobra este proveedor)' : '') + '</span></span></div>').join('') + '</div>'
            + '<div class="sug-pie">↑ ↓ para moverte · Enter para agregar · Esc para cerrar</div>';
        sug.innerHTML = h; sug.classList.add('abierta'); buscar.setAttribute('aria-expanded', 'true');
    }
    function cerrar() { sug.classList.remove('abierta'); buscar.setAttribute('aria-expanded', 'false'); activo = -1; }
    function mover(n) {
        const it = sug.querySelectorAll('.sug-item'); if (!it.length) return;
        activo = (n + it.length) % it.length; it.forEach((e, i) => e.classList.toggle('activo', i === activo)); it[activo].scrollIntoView({ block: 'nearest' });
    }
    function elegir(x) {
        if (!x) return;
        cerrar(); buscar.value = '';
        agregar(x.id, '', precioDe(x), 'base');
    }
    buscar.addEventListener('focus', () => pintarBuscador(true));
    buscar.addEventListener('click', () => pintarBuscador(true));
    buscar.addEventListener('input', () => pintarBuscador(true));
    buscar.addEventListener('blur', () => setTimeout(cerrar, 150));
    buscar.addEventListener('keydown', e => {
        if (e.key === 'ArrowDown') { e.preventDefault(); if (!sug.classList.contains('abierta')) pintarBuscador(true); mover(activo + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); mover(activo - 1); }
        else if (e.key === 'Enter') { e.preventDefault(); elegir(vis[activo >= 0 ? activo : 0]); }
        else if (e.key === 'Escape') { cerrar(); }
    });
    sug.addEventListener('mousedown', e => { const it = e.target.closest('.sug-item'); if (it) { e.preventDefault(); elegir(vis[+it.dataset.i]); } });
    // En la fila: Enter en el precio vuelve al buscador para el siguiente insumo
    $('ncItems').addEventListener('keydown', e => {
        if (e.key !== 'Enter') return;
        if (e.target.classList.contains('ni-c')) { e.preventDefault(); e.target.closest('.nc-item').querySelector('.ni-p').focus(); }
        else if (e.target.classList.contains('ni-p')) { e.preventDefault(); buscar.focus(); }
    });

    // ── Arranque: filas que vienen de "Registrar compra", "Repetir" o de un error ──
    (leer('ncIniciales') || []).forEach(it => agregar(it.id, it.cant, it.precio, it.unidad || 'base', true));
    pintarProveedor();
})();
</script>
@endpush
