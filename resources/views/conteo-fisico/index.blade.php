@extends('layouts.app')
@section('title', 'Conteo Físico')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li> <li class="breadcrumb-item active">Conteo Físico</li> @endsection

@include('partials.inv-estilos')

@push('styles')
<style>
    .cf-modos { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .cf-modo { display: flex; align-items: center; gap: .6rem; padding: .7rem 1rem; border-radius: 12px; background: #fff; border: 2px solid #eee3da; text-decoration: none; color: #495057; min-width: 240px; }
    .cf-modo:hover { border-color: #d4a98f; text-decoration: none; color: #1a1a2e; }
    .cf-modo.activo { border-color: #b5451b; background: #fffaf7; }
    .cf-modo i { font-size: 1.2rem; color: #b5451b; }
    .cf-modo b { display: block; color: #1a1a2e; }
    .cf-modo small { color: #8a8a9d; }
    body.dark-mode .cf-modo { background: #1f1f33; border-color: #33334d; color: #b0b0cc; }
    body.dark-mode .cf-modo.activo { background: rgba(181,69,27,.12); border-color: #b5451b; }
    body.dark-mode .cf-modo b { color: #f0f0f7; }

    .cf-ayuda { display: flex; gap: .8rem; align-items: flex-start; background: #f1f7fd; border: 1px solid #d6e7f7; color: #2c4a66; border-radius: 12px; padding: .8rem 1rem; font-size: .84rem; margin-bottom: 1rem; }
    .cf-ayuda i { color: #2980b9; font-size: 1.1rem; margin-top: .1rem; }
    body.dark-mode .cf-ayuda { background: #1d2a3a; border-color: #2b4058; color: #c9dcf0; }

    .cf-barra { display: flex; gap: .7rem; align-items: center; flex-wrap: wrap; margin-bottom: .8rem; }
    .cf-barra .search-box { flex: 1; min-width: 220px; }
    .cf-buscar { position: relative; }
    .cf-buscar input { padding-right: 2.2rem; }
    .cf-limpiar { position: absolute; right: .45rem; top: 50%; transform: translateY(-50%); border: none; background: #eef0f3; color: #6c757d; width: 24px; height: 24px; border-radius: 50%; font-size: .72rem; display: none; cursor: pointer; }
    .cf-buscar.con-texto .cf-limpiar { display: block; }
    .search-box.cf-buscar .cf-limpiar i { position: static; transform: none; color: inherit; font-size: .72rem; }
    .cf-filtros { display: flex; align-items: center; justify-content: space-between; gap: .6rem; flex-wrap: wrap; margin-bottom: .7rem; }
    .cf-filtros .inv-chips { margin: 0; }
    .cf-filtros .inv-chip { cursor: pointer; }
    .cf-filtros .inv-chip b { margin-left: .2rem; opacity: .75; }
    .cf-info { font-size: .8rem; color: #8a8a9d; }
    .cf-n mark { background: #ffe8a3; color: inherit; padding: 0 1px; border-radius: 3px; }
    body.dark-mode .cf-n mark { background: #6b5410; }
    .cf-vacio { text-align: center; padding: 2rem 1rem; color: #8a8a9d; background: #fff; border-radius: 14px; }
    .cf-vacio > i { font-size: 1.8rem; opacity: .4; display: block; margin-bottom: .5rem; }
    body.dark-mode .cf-vacio { background: #1f1f33; }
    .cf-ciego { display: flex; align-items: center; gap: .45rem; font-size: .84rem; color: #6c757d; cursor: pointer; margin: 0; }

    .cf-fila { display: grid; grid-template-columns: minmax(0, 1.5fr) 150px minmax(240px, 1.3fr) 130px; gap: .9rem; align-items: center; padding: .7rem 1.1rem; border-bottom: 1px solid #f3f0ec; }
    .cf-fila:last-child { border-bottom: none; }
    .cf-fila.contado { background: #fcfbf9; }
    .cf-fila.mal { background: rgba(231,76,60,.04); }
    body.dark-mode .cf-fila { border-bottom-color: #2c2c44; }
    body.dark-mode .cf-fila.contado { background: #22223a; }
    .cf-nombre b { color: #1a1a2e; }
    .cf-nombre small { display: block; color: #adb5bd; font-size: .74rem; }
    body.dark-mode .cf-nombre b { color: #f0f0f7; }
    .cf-sistema { font-size: .82rem; color: #8a8a9d; }
    .cf-sistema b { color: #1a1a2e; font-size: .95rem; }
    body.dark-mode .cf-sistema b { color: #f0f0f7; }
    .ciego .cf-sistema .cs-valor { filter: blur(6px); user-select: none; }
    .cf-entrada { display: flex; gap: .4rem; align-items: center; }
    .cf-entrada input { width: 120px; text-align: center; font-weight: 800; font-size: 1rem; }
    .cf-entrada select { width: 76px; }
    .cf-entrada .btn-igual { white-space: nowrap; font-size: .74rem; font-weight: 700; }
    .ciego .btn-igual { display: none; }
    .cf-dif { text-align: right; font-weight: 800; font-size: .88rem; }
    .cf-dif small { display: block; font-weight: 600; font-size: .72rem; color: #adb5bd; }
    .cf-dif.ok { color: #1e8e5a; } .cf-dif.mal { color: #c0392b; } .cf-dif.vacio { color: #cfd4da; }
    @media (max-width: 991px) {
        .cf-fila { grid-template-columns: 1fr auto; }
        .cf-entrada { grid-column: 1 / -1; }
    }

    .cf-pie { position: sticky; bottom: 0; z-index: 5; background: #fff; border-radius: 14px; box-shadow: 0 -6px 22px rgba(0,0,0,.08); padding: .9rem 1.2rem; margin-top: 1rem;
        display: grid; grid-template-columns: auto auto auto minmax(180px, 1fr) auto; gap: 1.2rem; align-items: center; }
    body.dark-mode .cf-pie { background: #1a1a2c; }
    .cf-pie .cp-dato small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #8a8a9d; }
    .cf-pie .cp-dato b { font-size: 1.25rem; color: #1a1a2e; }
    body.dark-mode .cf-pie .cp-dato b { color: #f0f0f7; }
    .cf-pie .cp-dato b.ok { color: #1e8e5a; } .cf-pie .cp-dato b.mal { color: #c0392b; }
    .cf-progreso { height: 6px; border-radius: 6px; background: #eef0f3; overflow: hidden; margin-top: .3rem; width: 120px; }
    .cf-progreso span { display: block; height: 100%; background: #b5451b; transition: width .2s; }
    @media (max-width: 991px) { .cf-pie { grid-template-columns: repeat(3, 1fr); } .cf-pie .cp-obs, .cf-pie .cp-btn { grid-column: 1 / -1; } }
    @media (max-width: 576px) { .cf-pie { padding: .55rem .8rem; gap: .35rem .6rem; } .cf-pie .cp-dato small { font-size: .58rem; } .cf-pie .cp-dato b { font-size: 1rem; } .cf-pie .cp-obs { grid-column: 1 / 3; } .cf-pie .cp-btn { grid-column: 3; } .cf-pie .cp-btn .btn { padding: .3rem .5rem; font-size: .8rem; white-space: nowrap; } .cf-pie .cp-btn .btn i { display: none; } }
</style>
@endpush

@section('content')
@php $C = \App\Support\Cantidad::class; @endphp
<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-clipboard-check mr-2 text-info"></i>Conteo Físico</h2>
        <p>Cuenta lo que hay en el almacén; el sistema compara, corrige el stock y calcula el PER</p>
    </div>
    <div class="toolbar-actions">
        @if(auth()->user()->hasPermiso('inventario.materia_prima'))
            <a href="{{ route('materia-prima.index') }}" class="btn btn-light"><i class="fas fa-arrow-left mr-1"></i>Materia Prima</a>
        @endif
        <a href="{{ route('conteo-fisico.historial') }}" class="btn btn-outline-secondary"><i class="fas fa-history mr-1"></i>Historial y PER</a>
    </div>
</div>

<div class="cf-modos">
    <a href="{{ route('conteo-fisico.index', ['modo' => 'control']) }}" class="cf-modo {{ $modo === 'control' ? 'activo' : '' }}">
        <i class="fas fa-bullseye"></i>
        <span><b>Insumos de control</b><small>{{ $hayControl ? 'Conteo rápido para el PER' : 'Marca insumos como "de control" en su ficha' }}</small></span>
    </a>
    <a href="{{ route('conteo-fisico.index', ['modo' => 'todos']) }}" class="cf-modo {{ $modo === 'todos' ? 'activo' : '' }}">
        <i class="fas fa-boxes"></i>
        <span><b>Todos los insumos</b><small>Inventario completo del almacén</small></span>
    </a>
</div>

<div class="cf-ayuda">
    <i class="fas fa-info-circle"></i>
    <div>
        Escribe <b>lo que cuentas</b> en el almacén; deja vacío lo que no cuentes hoy. El PER es
        <b>insumos con diferencia ÷ insumos contados × 100</b> (0 % = todo coincide).
        @if($ultimo) El último conteo ({{ \Carbon\Carbon::parse($ultimo->fecha)->format('d/m H:i') }}) tuvo un PER de <b>{{ $ultimo->per }} %</b>. @endif
    </div>
</div>

@if($materias->isEmpty())
    <div class="empty-state"><i class="fas fa-clipboard-check"></i><p>No hay insumos para contar en este modo.</p></div>
@else
<form action="{{ route('conteo-fisico.store') }}" method="POST" id="formConteo" class="js-confirm"
      data-confirm-title="¿Guardar el conteo?" data-confirm="">
    @csrf
    <input type="hidden" name="modo" value="{{ $modo }}">

    <div class="cf-barra">
        <div class="search-box cf-buscar">
            <i class="fas fa-search"></i>
            <input type="text" id="cfBuscar" class="form-control" placeholder="Buscar insumo… (Enter para contarlo)" autocomplete="off"
                   value="{{ request('q') }}" aria-label="Buscar insumo en la lista">
            <button type="button" id="cfLimpiar" class="cf-limpiar" title="Borrar búsqueda (Esc)" aria-label="Borrar búsqueda"><i class="fas fa-times"></i></button>
        </div>
        <label class="cf-ciego" title="Para contar sin dejarse influir por lo que dice el sistema">
            <input type="checkbox" id="cfCiego"> Ocultar lo que dice el sistema (conteo a ciegas)
        </label>
    </div>
    <div class="cf-filtros">
        <div class="inv-chips" role="group" aria-label="Mostrar">
            <button type="button" class="inv-chip activo" data-f="todos">Todos <b data-n="todos"></b></button>
            <button type="button" class="inv-chip" data-f="pendientes">Por contar <b data-n="pendientes"></b></button>
            <button type="button" class="inv-chip" data-f="contados">Contados <b data-n="contados"></b></button>
            <button type="button" class="inv-chip" data-f="dif">Con diferencia <b data-n="dif"></b></button>
        </div>
        <span class="cf-info" id="cfInfo"></span>
    </div>

    <div class="inv-lista" id="cfLista">
        @foreach($materias as $m)
            @php $u = $m->unidad->abreviatura ?? ''; $sub = \App\Http\Controllers\ProduccionController::subunidad($u); @endphp
            <div class="cf-fila" data-nombre="{{ mb_strtolower($m->nombre) }}" data-id="{{ $m->id }}" data-sistema="{{ $m->stock_actual }}" data-u="{{ $u }}" data-costo="{{ (float) $m->costo_unitario }}">
                <div class="cf-nombre"><b class="cf-n">{{ $m->nombre }}</b><small>{{ $m->es_control ? 'Insumo de control · ' : '' }}S/ {{ number_format($m->costo_unitario, 2) }} por {{ $u }}</small></div>
                <div class="cf-sistema">Sistema: <b class="cs-valor">{{ $C::conUnidad($m->stock_actual, $u) }}</b></div>
                <div class="cf-entrada">
                    <input type="number" name="conteo[{{ $m->id }}][stock_fisico]" class="form-control cf-input" step="any" min="0" placeholder="—"
                           value="{{ old('conteo.' . $m->id . '.stock_fisico') }}" aria-label="Cantidad contada de {{ $m->nombre }}">
                    <select name="conteo[{{ $m->id }}][unidad]" class="form-control cf-unidad" {{ $sub ? '' : 'hidden' }} aria-label="Unidad">
                        <option value="base">{{ $u }}</option>
                        @if($sub)<option value="sub" {{ $m->stock_actual < 1 ? 'selected' : '' }}>{{ $sub }}</option>@endif
                    </select>
                    @if(!$sub)<span class="text-muted small">{{ $u }}</span>@endif
                    <button type="button" class="btn btn-sm btn-light btn-igual" title="Lo contado coincide con el sistema">= Coincide</button>
                </div>
                <div class="cf-dif vacio">—</div>
            </div>
        @endforeach
    </div>

    <div class="cf-vacio" id="cfVacio" hidden>
        <i class="fas fa-search"></i>
        <p>Ningún insumo coincide con «<b id="cfVacioQ"></b>»<span id="cfVacioFiltro"></span>.</p>
        <button type="button" class="btn btn-sm btn-light" id="cfVerTodo">Ver toda la lista</button>
        @if($modo === 'control')
            <a href="{{ route('conteo-fisico.index', ['modo' => 'todos']) }}" class="btn btn-sm btn-outline-secondary" id="cfEnTodos">Buscar en todos los insumos</a>
            <small class="mt-2 text-muted" id="cfEnTodosAviso" style="display:none">Lo que ya contaste aquí se perdería: guarda este conteo primero.</small>
        @endif
    </div>

    <div class="cf-pie">
        <div class="cp-dato"><small>Contados</small><b id="pContados">0</b> / {{ $materias->count() }}<div class="cf-progreso"><span id="pBarra" style="width:0"></span></div></div>
        <div class="cp-dato"><small>Con diferencia</small><b id="pDif">0</b></div>
        <div class="cp-dato"><small>PER</small><b id="pPer">—</b></div>
        <div class="cp-obs"><input type="text" name="observacion_general" maxlength="200" class="form-control form-control-sm" placeholder="Nota del conteo (opcional)" value="{{ old('observacion_general') }}"></div>
        <div class="cp-btn"><button type="submit" class="btn btn-primary" id="btnGuardar" disabled><i class="fas fa-save mr-1"></i>Guardar conteo</button></div>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('formConteo');
    if (!form) return;
    const filas = [...document.querySelectorAll('.cf-fila')];
    const $ = id => document.getElementById(id);
    const fmtS = n => 'S/ ' + Math.abs(n).toFixed(2);

    function calcular(fila) {
        const inp = fila.querySelector('.cf-input'), sel = fila.querySelector('.cf-unidad'), dif = fila.querySelector('.cf-dif');
        const sistema = parseFloat(fila.dataset.sistema), u = fila.dataset.u, costo = parseFloat(fila.dataset.costo);
        fila.classList.remove('contado', 'mal');
        if (inp.value === '') { dif.className = 'cf-dif vacio'; dif.textContent = '—'; return null; }
        const f = sel && sel.value === 'sub' ? 1000 : 1;
        const d = +((parseFloat(inp.value) || 0) / f - sistema).toFixed(3);
        fila.classList.add('contado');
        if (Math.abs(d) < 0.0005) { dif.className = 'cf-dif ok'; dif.innerHTML = '<i class="fas fa-check mr-1"></i>Coincide'; return 0; }
        fila.classList.add('mal');
        dif.className = 'cf-dif mal';
        dif.innerHTML = (d > 0 ? 'Sobra ' : 'Falta ') + fmtCantU(Math.abs(d), u) + '<small>' + fmtS(d * costo) + '</small>';
        return d;
    }

    function resumen() {
        let contados = 0, conDif = 0;
        filas.forEach(fila => { const d = calcular(fila); if (d !== null) { contados++; if (d !== 0) conDif++; } });
        const per = contados ? Math.round(conDif / contados * 1000) / 10 : null;
        $('pContados').textContent = contados;
        $('pBarra').style.width = (contados / filas.length * 100) + '%';
        $('pDif').textContent = conDif;
        $('pDif').className = conDif ? 'mal' : (contados ? 'ok' : '');
        $('pPer').textContent = per === null ? '—' : per + ' %';
        $('pPer').className = per === null ? '' : (per === 0 ? 'ok' : 'mal');
        $('btnGuardar').disabled = contados === 0;
        form.dataset.confirm = contados === 0 ? '' :
            `Contaste ${contados} insumo(s): ${conDif ? conDif + ' no coinciden y se corregirá su stock' : 'todos coinciden con el sistema'}. PER de este conteo: ${per} %.`;
    }

    filas.forEach(fila => {
        fila.querySelector('.cf-input').addEventListener('input', resumen);
        const sel = fila.querySelector('.cf-unidad'); if (sel) sel.addEventListener('change', resumen);
        fila.querySelector('.btn-igual').addEventListener('click', () => {
            const s = fila.querySelector('.cf-unidad');
            const f = s && s.value === 'sub' ? 1000 : 1;
            fila.querySelector('.cf-input').value = +(parseFloat(fila.dataset.sistema) * f).toFixed(3);
            resumen();
        });
        // Enter pasa al siguiente insumo en vez de enviar el formulario
        fila.querySelector('.cf-input').addEventListener('keydown', e => {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            const sig = filas.slice(filas.indexOf(fila) + 1).find(x => x.style.display !== 'none');
            if (sig) sig.querySelector('.cf-input').focus();
        });
    });

    // ── Búsqueda: sin tildes, varias palabras, resalta lo encontrado y filtra por estado ──
    const norm = t => (t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const esc = t => t.replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    filas.forEach(f => { const b = f.querySelector('.cf-n'); b.dataset.txt = b.textContent; f.dataset.norm = norm(b.dataset.txt); });
    let filtro = 'todos';
    const nombresFiltro = { pendientes: ' entre los que faltan contar', contados: ' entre los contados', dif: ' entre los que tienen diferencia' };

    function resaltar(b, palabras) {
        const txt = b.dataset.txt;
        if (!palabras.length) { b.textContent = txt; return; }
        // marca cada palabra buscada (comparando sin tildes, pero mostrando el texto original)
        const n = norm(txt), marcas = new Array(txt.length).fill(false);
        palabras.forEach(p => { let i = n.indexOf(p); while (i !== -1) { for (let k = i; k < i + p.length; k++) marcas[k] = true; i = n.indexOf(p, i + 1); } });
        let html = '', abierto = false;
        for (let k = 0; k < txt.length; k++) {
            if (marcas[k] && !abierto) { html += '<mark>'; abierto = true; }
            if (!marcas[k] && abierto) { html += '</mark>'; abierto = false; }
            html += esc(txt[k]);
        }
        b.innerHTML = html + (abierto ? '</mark>' : '');
    }

    function estado(f) { return !f.classList.contains('contado') ? 'pendientes' : (f.classList.contains('mal') ? 'dif' : 'ok'); }

    function filtrar(soloCuentas) {
        const q = $('cfBuscar').value;
        const palabras = norm(q).split(/\s+/).filter(Boolean);
        $('cfBuscar').parentElement.classList.toggle('con-texto', q !== '');
        const cuenta = { todos: filas.length, pendientes: 0, contados: 0, dif: 0 };
        let visibles = 0;
        filas.forEach(f => {
            const e = estado(f);
            if (e === 'pendientes') cuenta.pendientes++; else { cuenta.contados++; if (e === 'dif') cuenta.dif++; }
            const pasaTexto = palabras.every(p => f.dataset.norm.includes(p));
            const pasaFiltro = filtro === 'todos' || (filtro === 'pendientes' && e === 'pendientes') || (filtro === 'contados' && e !== 'pendientes') || (filtro === 'dif' && e === 'dif');
            // mientras escribes una cantidad la fila no desaparece; se reordena al cambiar de filtro o buscar
            const ver = soloCuentas === true ? f.style.display !== 'none' : (pasaTexto && pasaFiltro);
            f.style.display = ver ? '' : 'none';
            if (ver) visibles++;
            if (soloCuentas !== true) resaltar(f.querySelector('.cf-n'), ver ? palabras : []);
        });
        document.querySelectorAll('[data-n]').forEach(b => b.textContent = cuenta[b.dataset.n]);
        $('cfInfo').textContent = visibles === filas.length ? filas.length + ' insumos' : 'Mostrando ' + visibles + ' de ' + filas.length;
        $('cfVacio').hidden = visibles > 0;
        $('cfLista').style.display = visibles ? '' : 'none';
        $('cfVacioQ').textContent = q.trim() || '—';
        $('cfVacioQ').parentElement.firstChild.textContent = q.trim() ? 'Ningún insumo coincide con «' : 'No hay insumos ';
        $('cfVacioQ').style.display = q.trim() ? '' : 'none';
        $('cfVacioQ').nextSibling.textContent = q.trim() ? '»' : '';
        $('cfVacioFiltro').textContent = filtro === 'todos' ? '' : nombresFiltro[filtro];
        const enTodos = $('cfEnTodos');
        if (enTodos) {
            enTodos.href = enTodos.href.split('&q=')[0] + (q.trim() ? '&q=' + encodeURIComponent(q.trim()) : '');
            enTodos.style.display = cuenta.contados ? 'none' : '';
            $('cfEnTodosAviso').style.display = cuenta.contados ? 'block' : 'none';
        }
    }

    function primeraVisible() { return filas.find(f => f.style.display !== 'none'); }
    function limpiar() { $('cfBuscar').value = ''; filtrar(); $('cfBuscar').focus(); }

    $('cfBuscar').addEventListener('input', () => filtrar());
    $('cfBuscar').addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === 'ArrowDown') {
            e.preventDefault();
            const f = primeraVisible(); if (f) { f.querySelector('.cf-input').focus(); f.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
        } else if (e.key === 'Escape') { limpiar(); }
    });
    $('cfLimpiar').addEventListener('click', limpiar);
    $('cfVerTodo').addEventListener('click', () => {
        filtro = 'todos';
        document.querySelectorAll('.cf-filtros [data-f]').forEach(x => x.classList.toggle('activo', x.dataset.f === 'todos'));
        limpiar();
    });
    document.querySelectorAll('.cf-filtros [data-f]').forEach(b => b.addEventListener('click', () => {
        filtro = b.dataset.f;
        document.querySelectorAll('.cf-filtros [data-f]').forEach(x => x.classList.toggle('activo', x === b));
        filtrar();
    }));
    // "/" lleva al buscador desde cualquier parte (si no estás escribiendo en otro campo)
    document.addEventListener('keydown', e => {
        if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) { e.preventDefault(); $('cfBuscar').focus(); $('cfBuscar').select(); }
    });
    // al terminar de escribir una cantidad con la búsqueda activa, Enter vuelve al buscador
    filas.forEach(fila => fila.querySelector('.cf-input').addEventListener('keydown', e => {
        if (e.key === 'Enter' && $('cfBuscar').value.trim()) { e.stopImmediatePropagation(); e.preventDefault(); $('cfBuscar').focus(); $('cfBuscar').select(); }
    }, true));
    form.addEventListener('input', e => { if (e.target.classList.contains('cf-input')) filtrar(true); });
    form.addEventListener('click', e => { if (e.target.closest('.btn-igual')) setTimeout(() => filtrar(true), 0); });

    $('cfCiego').addEventListener('change', function () { $('cfLista').classList.toggle('ciego', this.checked); });

    resumen();
    filtrar();
    if ($('cfBuscar').value) $('cfBuscar').focus();
})();
</script>
@endpush
