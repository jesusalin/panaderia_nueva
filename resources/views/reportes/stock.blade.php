@extends('layouts.app')
@section('title', 'Reporte de Stock')
@section('breadcrumb') <li class="breadcrumb-item active">Reporte de Stock</li> @endsection

@push('styles')
<style>
    .rp-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .rp-stat { background: var(--m-surface); border-radius: 14px; padding: 1.1rem 1.3rem; box-shadow: 0 2px 12px rgba(0,0,0,.05); border-left: 4px solid var(--m-border-strong); }
    .rp-stat.acc-info    { border-left-color: #3498db; }
    .rp-stat.acc-warn    { border-left-color: #f39c12; }
    .rp-stat.acc-danger  { border-left-color: #e74c3c; }
    .rp-stat.acc-success { border-left-color: #2ecc71; }
    .rp-stat .rp-label { font-size: .74rem; font-weight: 700; color: var(--m-muted); text-transform: uppercase; letter-spacing: .03em; }
    .rp-stat .rp-value { font-size: 1.4rem; font-weight: 800; color: var(--m-text); margin-top: .2rem; }
    body.dark-mode .rp-stat { background: #1f1f33; }
    body.dark-mode .rp-stat .rp-value { color: #f0f0f7; }

    .rp-filter { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1rem 1.3rem; margin-bottom: 1.3rem; }
    .rp-filter .form-control { border-radius: 10px; border: 1.5px solid var(--m-border); }
    body.dark-mode .rp-filter .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }

    .rp-section-title { font-weight: 800; color: var(--m-text); font-size: .95rem; margin: 1.5rem 0 .8rem; display: flex; align-items: center; gap: .5rem; }
    body.dark-mode .rp-section-title { color: #f0f0f7; }

    .nivel { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 800; padding: .2rem .6rem; border-radius: 20px; text-transform: uppercase; letter-spacing: .03em; }
    .nivel::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .nivel.agotado { background: rgba(231,76,60,.14); color: var(--m-danger-text); }
    .nivel.bajo    { background: rgba(243,156,18,.16); color: var(--m-warning-text); }
    .nivel.ok      { background: rgba(46,204,113,.14); color: var(--m-success-text); }
    body.dark-mode .nivel.agotado { color: #ff9b8f; }
    body.dark-mode .nivel.bajo    { color: #ffc673; }
    body.dark-mode .nivel.ok      { color: #6ee7a5; }

    .reponer-hint { font-size: .78rem; font-weight: 700; color: var(--m-brand-text); }
    body.dark-mode .reponer-hint { color: #ff9d6e; }
    .sk-orden { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin: -.3rem 0 .8rem; }
    .sk-orden-lbl { font-size: .78rem; font-weight: 700; color: var(--m-muted); text-transform: uppercase; letter-spacing: .03em; margin-right: .2rem; }
    .sk-orden-op { display: inline-flex; align-items: center; padding: .32rem .8rem; border-radius: 20px; font-size: .82rem; font-weight: 700;
                   background: var(--m-surface-3); color: var(--m-text-2); border: 1.5px solid transparent; text-decoration: none !important; transition: all .15s; }
    .sk-orden-op:hover { background: #e7e7ec; color: var(--m-text); }
    .sk-orden-op.activo { background: rgba(181,69,27,.12); color: var(--m-brand-text); border-color: rgba(181,69,27,.4); }
    .sk-orden-dir { margin-left: .45rem; padding-left: .45rem; border-left: 1.5px solid rgba(181,69,27,.3); font-weight: 600; font-size: .76rem; }
    body.dark-mode .sk-orden-op { background: #2c2c44; color: #b0b0cc; }
    body.dark-mode .sk-orden-op.activo { background: rgba(181,69,27,.25); color: #ff9d6e; border-color: rgba(255,157,110,.4); }
    .sk-vista { display: flex; gap: .7rem; align-items: flex-start; background: rgba(33,112,163,.07); border: 1px solid rgba(33,112,163,.18);
                border-radius: 12px; padding: .65rem .9rem; font-size: .84rem; color: #2a4a63; margin-bottom: 1rem; }
    .sk-vista > i { color: var(--m-info-text); margin-top: .2rem; }
    .sk-vista-cerrar { background: none; border: none; font-size: 1.35rem; line-height: 1; color: var(--m-info-text); opacity: .55; padding: 0 .2rem; margin-left: .3rem; cursor: pointer; }
    .sk-vista-cerrar:hover { opacity: 1; }
    body.dark-mode .sk-vista-cerrar { color: #8cc8f0; }
    body.dark-mode .sk-vista { background: rgba(110,180,230,.08); border-color: rgba(110,180,230,.2); color: #b8d6ec; }
    .sk-acciones { display: flex; gap: .35rem; margin-top: .35rem; justify-content: flex-end; }
    .sk-acciones .btn { padding: .15rem .5rem; font-size: .75rem; border-radius: 8px; }
</style>
@endpush

@include('partials.reportes-estilos')

@section('content')

@php
    $todos = $materias->concat($productos);
    $totalItems     = $todos->count();
    $totalAgotados  = $todos->where('nivel', 'agotado')->count();
    $totalBajos     = $todos->where('nivel', 'bajo')->count();
    $puedeAjustar   = auth()->user()->hasPermiso('inventario.materia_prima');
    $puedeComprar   = auth()->user()->hasPermiso('compras.compras');
    $puedeOrdenes   = auth()->user()->hasPermiso('compras.ordenes');
    $puedeProducir  = auth()->user()->hasPermiso('produccion.produccion');
    $puedeKardex    = auth()->user()->hasPermiso('reportes.kardex');
    $etiquetaNivel  = ['agotado' => 'Agotado', 'bajo' => 'Stock bajo', 'ok' => 'Normal'];
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-warehouse mr-2 text-info"></i>Reporte de Stock</h2>
        <p>Qué hay, qué falta y cuánto reponer — materia prima y productos terminados</p>
    </div>
    <div class="toolbar-actions">
        @if($puedeOrdenes && $materias->whereIn('nivel', ['agotado','bajo'])->count() > 0)
        <form action="{{ route('ordenes-automaticas.generar') }}" method="POST" class="d-inline">
            @csrf
            <button class="btn btn-warning" title="Crea una orden de compra sugerida por cada insumo con stock bajo">
                <i class="fas fa-bolt mr-1"></i>Generar órdenes de reposición
            </button>
        </form>
        @endif
        <a href="{{ route('reportes.stock.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-secondary">
            <i class="fas fa-file-pdf mr-1"></i>PDF
        </a>
        <a href="{{ route('reportes.stock.excel', request()->query()) }}" class="btn btn-outline-success">
            <i class="fas fa-file-excel mr-1"></i>Excel
        </a>
    </div>
</div>

<div class="rp-stat-grid">
    <div class="rp-stat acc-info">
        <div class="rp-label">Ítems en inventario</div>
        <div class="rp-value">{{ $totalItems }}</div>
    </div>
    <div class="rp-stat acc-danger">
        <div class="rp-label">Agotados</div>
        <div class="rp-value">{{ $totalAgotados }}</div>
    </div>
    <div class="rp-stat acc-warn">
        <div class="rp-label">Con stock bajo</div>
        <div class="rp-value">{{ $totalBajos }}</div>
    </div>
    <div class="rp-stat acc-success">
        <div class="rp-label">Valor total</div>
        <div class="rp-value">S/ {{ number_format($valorMateria + $valorProducto, 2) }}</div>
    </div>
</div>

<form method="GET" id="formStock" class="filter-bar flex-wrap" style="gap:.6rem">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" id="inBuscar" class="form-control" autocomplete="off"
               data-sugerencias="{{ ['materia' => 'insumos', 'producto' => 'productos'][$tipo ?? 'todos'] ?? 'stock' }}"
               placeholder="Buscar insumo o producto…" value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Mostrar</label>
    <select name="tipo" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="todos"    {{ $tipo === 'todos'    ? 'selected' : '' }}>Todo el inventario</option>
        <option value="materia"  {{ $tipo === 'materia'  ? 'selected' : '' }}>Solo materia prima</option>
        <option value="producto" {{ $tipo === 'producto' ? 'selected' : '' }}>Solo productos</option>
    </select>
    <label class="fb-label mb-0">Nivel</label>
    <select name="stock" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos los niveles</option>
        <option value="bajo"    {{ request('stock') === 'bajo'    ? 'selected' : '' }}>Necesitan reposición (bajo + agotado)</option>
        <option value="agotado" {{ request('stock') === 'agotado' ? 'selected' : '' }}>Solo agotados</option>
    </select>
    @if($orden !== '')<input type="hidden" name="orden" value="{{ $orden }}">@endif
    @if(request('dir'))<input type="hidden" name="dir" value="{{ $dir }}">@endif
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('stock') || request('orden') || request('dir') || ($tipo ?? 'todos') !== 'todos')
        <a href="{{ route('reportes.stock') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

{{-- Ordenar: a la vista, con un toque. Tocar el orden activo invierte el sentido. --}}
<div class="sk-orden">
    <span class="sk-orden-lbl"><i class="fas fa-sort-amount-down mr-1"></i>Ordenar por</span>
    @foreach(\App\Http\Controllers\ReportesController::ORDENES_STOCK as $clave => $o)
        @php
            $activo = $orden === $clave;
            $nuevoDir = $activo ? ($dir === 'asc' ? 'desc' : 'asc') : $o['dir'];
            $enlace = array_filter(array_merge(request()->except(['orden', 'dir', 'page']), ['orden' => $clave ?: null, 'dir' => $nuevoDir !== $o['dir'] ? $nuevoDir : null]));
        @endphp
        <a href="{{ route('reportes.stock', $enlace) }}" class="sk-orden-op {{ $activo ? 'activo' : '' }}"
           title="{{ $activo ? 'Tocar otra vez para invertir: ' . $o[$nuevoDir] : $o['texto'] . ' (' . $o[$o['dir']] . ')' }}">
            <i class="fas {{ $o['icono'] }} mr-1"></i>{{ $o['texto'] }}
            @if($activo)<span class="sk-orden-dir"><i class="fas {{ $dir === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }} mr-1"></i>{{ $o[$dir] }}</span>@endif
        </a>
    @endforeach
</div>

<div class="sk-vista" id="avisoStockVista" role="note">
    <i class="fas fa-file-export"></i>
    <div class="flex-grow-1"><b>El PDF y el Excel salen tal como ves la lista:</b> {{ $vista }}.
        <span class="text-muted">Cambia los filtros o el orden de arriba para descargar otra cosa.</span></div>
    <button type="button" class="sk-vista-cerrar" id="cerrarAvisoStock" aria-label="Cerrar aviso" title="Cerrar (no vuelve a salir)">&times;</button>
</div>
<script>try { if (localStorage.getItem('avisoStockVistaCerrado')) document.getElementById('avisoStockVista').remove(); } catch (e) {}</script>

@if($materias->count() > 0)
<div class="rp-section-title"><i class="fas fa-wheat-awn text-secondary"></i>Materia Prima</div>
<div class="list-rows">
    @foreach($materias as $m)
        <div class="list-row">
            <div class="lr-icon {{ $m->nivel === 'ok' ? '' : 'danger' }}"><i class="fas fa-wheat-awn"></i></div>
            <div class="lr-main">
                <div class="lr-title">{{ $m->nombre }} <span class="nivel {{ $m->nivel }} ml-1">{{ $etiquetaNivel[$m->nivel] }}</span></div>
                <div class="lr-subtitle">{{ $m->proveedor->nombre ?? 'Sin proveedor' }}</div>
            </div>
            <div class="lr-meta">
                <div class="lm-item"><span class="lm-label">Stock</span><span class="lm-value {{ $m->nivel === 'ok' ? '' : 'text-danger' }}">{{ \App\Support\Cantidad::conUnidad($m->stock_actual, $m->unidad->abreviatura) }}</span></div>
                <div class="lm-item"><span class="lm-label">Mínimo</span><span class="lm-value">{{ \App\Support\Cantidad::conUnidad($m->stock_minimo, $m->unidad->abreviatura) }}</span></div>
                <div class="lm-item"><span class="lm-label">Costo Unit.</span><span class="lm-value">S/ {{ number_format($m->costo_unitario, 2) }}</span></div>
                <div class="lm-item" title="{{ $m->por_dia ? 'Se usan ≈ ' . \App\Support\Cantidad::conUnidad(round($m->por_dia, $m->por_dia >= 1 ? 2 : 3), $m->unidad->abreviatura) . ' al día' : 'Sin consumo en los últimos 14 días' }}">
                    <span class="lm-label">Alcanza</span>
                    <span class="lm-value {{ $m->dias !== null && $m->dias <= 2 ? 'text-danger' : '' }}">{{ $m->dias === null ? '—' : ($m->dias < 1 ? '< 1 día' : $m->dias . ' día' . ($m->dias == 1 ? '' : 's')) }}</span>
                </div>
            </div>
            <div class="lr-side">
                <span class="lr-amount">S/ {{ number_format($m->valor, 2) }}</span>
                @if($m->reponer > 0)
                    <div class="reponer-hint">Reponer ≈ {{ \App\Support\Cantidad::conUnidad($m->reponer, $m->unidad->abreviatura) }}</div>
                @endif
                <div class="sk-acciones">
                    @if($puedeKardex)<a href="{{ route('reportes.kardex', ['item' => 'materia-'.$m->id]) }}" class="btn btn-outline-info" title="Ver su kardex"><i class="fas fa-file-invoice"></i></a>@endif
                    @if($puedeAjustar)<a href="{{ route('materia-prima.ajuste', $m) }}" class="btn btn-outline-warning" title="Ajustar stock"><i class="fas fa-balance-scale"></i></a>@endif
                    @if($puedeComprar && $m->nivel !== 'ok')<a href="{{ route('compras.create', ['materia' => $m->id]) }}" class="btn btn-outline-primary" title="Registrar compra"><i class="fas fa-cart-plus"></i></a>@endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

@if($productos->count() > 0)
<div class="rp-section-title"><i class="fas fa-bread-slice text-warning"></i>Productos Terminados</div>
<div class="list-rows">
    @foreach($productos as $p)
        <div class="list-row">
            <div class="lr-icon {{ $p->nivel === 'ok' ? 'success' : 'danger' }}"><i class="fas fa-bread-slice"></i></div>
            <div class="lr-main">
                <div class="lr-title">{{ $p->nombre }} <span class="nivel {{ $p->nivel }} ml-1">{{ $etiquetaNivel[$p->nivel] }}</span></div>
                <div class="lr-subtitle">{{ $p->categoria->nombre ?? 'Sin categoría' }}</div>
            </div>
            <div class="lr-meta">
                <div class="lm-item"><span class="lm-label">Stock</span><span class="lm-value {{ $p->nivel === 'ok' ? '' : 'text-danger' }}">{{ $p->stock_actual }} uds</span></div>
                <div class="lm-item"><span class="lm-label">Mínimo</span><span class="lm-value">{{ $p->stock_minimo }} uds</span></div>
                <div class="lm-item"><span class="lm-label">Costo Unit.</span><span class="lm-value">S/ {{ number_format($p->costo_produccion ?? 0, 2) }}</span></div>
            </div>
            <div class="lr-side">
                <span class="lr-amount">S/ {{ number_format($p->valor, 2) }}</span>
                @if($p->reponer > 0)
                    <div class="reponer-hint">Producir ≈ {{ number_format($p->reponer, 0) }} uds</div>
                @endif
                <div class="sk-acciones">
                    @if($puedeKardex)<a href="{{ route('reportes.kardex', ['item' => 'producto-'.$p->id]) }}" class="btn btn-outline-info" title="Ver su kardex"><i class="fas fa-file-invoice"></i></a>@endif
                    @if($puedeProducir && $p->nivel !== 'ok')<a href="{{ route('produccion.create') }}" class="btn btn-outline-primary" title="Registrar producción"><i class="fas fa-industry"></i></a>@endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endif

@if($materias->isEmpty() && $productos->isEmpty())
<div class="empty-state">
    <i class="fas fa-warehouse"></i>
    <p>No se encontraron ítems con ese filtro.</p>
</div>
@endif

@endsection

@push('scripts')
<script>
    // El buscador muestra sugerencias de insumos y productos (ver layout: data-sugerencias).
    // Aviso de cómo sale el PDF/Excel: con la X se cierra y no vuelve a salir en este navegador.
    (function () {
        const aviso = document.getElementById('avisoStockVista'), CLAVE = 'avisoStockVistaCerrado';
        if (!aviso) return;
        try { if (localStorage.getItem(CLAVE)) aviso.remove(); } catch (e) {}
        document.getElementById('cerrarAvisoStock')?.addEventListener('click', function () {
            aviso.remove();
            try { localStorage.setItem(CLAVE, '1'); } catch (e) {}
        });
    })();
</script>
@endpush
