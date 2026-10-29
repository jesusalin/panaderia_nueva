@extends('layouts.app')
@section('title', 'Reporte de Stock')
@section('breadcrumb') <li class="breadcrumb-item active">Reporte de Stock</li> @endsection

@push('styles')
<style>
    .rp-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .rp-stat { background: #fff; border-radius: 14px; padding: 1.1rem 1.3rem; box-shadow: 0 2px 12px rgba(0,0,0,.05); border-left: 4px solid #dee2e6; }
    .rp-stat.acc-info    { border-left-color: #3498db; }
    .rp-stat.acc-warn    { border-left-color: #f39c12; }
    .rp-stat.acc-danger  { border-left-color: #e74c3c; }
    .rp-stat.acc-success { border-left-color: #2ecc71; }
    .rp-stat .rp-label { font-size: .74rem; font-weight: 700; color: #8a8a9d; text-transform: uppercase; letter-spacing: .03em; }
    .rp-stat .rp-value { font-size: 1.4rem; font-weight: 800; color: #1a1a2e; margin-top: .2rem; }
    body.dark-mode .rp-stat { background: #1f1f33; }
    body.dark-mode .rp-stat .rp-value { color: #f0f0f7; }

    .rp-filter { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1rem 1.3rem; margin-bottom: 1.3rem; }
    .rp-filter .form-control { border-radius: 10px; border: 1.5px solid #e9ecef; }
    body.dark-mode .rp-filter .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }

    .rp-section-title { font-weight: 800; color: #1a1a2e; font-size: .95rem; margin: 1.5rem 0 .8rem; display: flex; align-items: center; gap: .5rem; }
    body.dark-mode .rp-section-title { color: #f0f0f7; }

    .nivel { display: inline-flex; align-items: center; gap: .35rem; font-size: .72rem; font-weight: 800; padding: .2rem .6rem; border-radius: 20px; text-transform: uppercase; letter-spacing: .03em; }
    .nivel::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .nivel.agotado { background: rgba(231,76,60,.14); color: #c0392b; }
    .nivel.bajo    { background: rgba(243,156,18,.16); color: #a5680d; }
    .nivel.ok      { background: rgba(46,204,113,.14); color: #1e8e5a; }
    body.dark-mode .nivel.agotado { color: #ff9b8f; }
    body.dark-mode .nivel.bajo    { color: #ffc673; }
    body.dark-mode .nivel.ok      { color: #6ee7a5; }

    .reponer-hint { font-size: .78rem; font-weight: 700; color: #b5451b; }
    body.dark-mode .reponer-hint { color: #ff9d6e; }
    .sk-acciones { display: flex; gap: .35rem; margin-top: .35rem; justify-content: flex-end; }
    .sk-acciones .btn { padding: .15rem .5rem; font-size: .75rem; border-radius: 8px; }
</style>
@endpush

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

<div class="card rp-filter">
    <form method="GET" id="formStock" class="form-inline flex-wrap" style="gap:.6rem;">
        <input type="text" name="buscar" id="inBuscar" class="form-control" placeholder="Buscar por nombre…" value="{{ request('buscar') }}" autocomplete="off">
        <select name="tipo" class="form-control" onchange="this.form.submit()">
            <option value="todos"    {{ $tipo === 'todos'    ? 'selected' : '' }}>Todo el inventario</option>
            <option value="materia"  {{ $tipo === 'materia'  ? 'selected' : '' }}>Solo Materia Prima</option>
            <option value="producto" {{ $tipo === 'producto' ? 'selected' : '' }}>Solo Productos</option>
        </select>
        <select name="stock" class="form-control" onchange="this.form.submit()">
            <option value="">Todos los niveles</option>
            <option value="bajo"    {{ request('stock') === 'bajo'    ? 'selected' : '' }}>Necesitan reposición (bajo + agotado)</option>
            <option value="agotado" {{ request('stock') === 'agotado' ? 'selected' : '' }}>Solo agotados</option>
        </select>
        @if(request('buscar') || request('stock') || ($tipo ?? 'todos') !== 'todos')
            <a href="{{ route('reportes.stock') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
        @endif
    </form>
</div>

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
                <div class="lm-item"><span class="lm-label">Stock</span><span class="lm-value {{ $m->nivel === 'ok' ? '' : 'text-danger' }}">{{ \App\Support\Cantidad::fmt($m->stock_actual) }} {{ $m->unidad->abreviatura }}</span></div>
                <div class="lm-item"><span class="lm-label">Mínimo</span><span class="lm-value">{{ \App\Support\Cantidad::fmt($m->stock_minimo) }} {{ $m->unidad->abreviatura }}</span></div>
                <div class="lm-item"><span class="lm-label">Costo Unit.</span><span class="lm-value">S/ {{ number_format($m->costo_unitario, 2) }}</span></div>
            </div>
            <div class="lr-side">
                <span class="lr-amount">S/ {{ number_format($m->valor, 2) }}</span>
                @if($m->reponer > 0)
                    <div class="reponer-hint">Reponer ≈ {{ \App\Support\Cantidad::fmt($m->reponer) }} {{ $m->unidad->abreviatura }}</div>
                @endif
                <div class="sk-acciones">
                    @if($puedeKardex)<a href="{{ route('reportes.kardex', ['item' => 'materia-'.$m->id]) }}" class="btn btn-outline-info" title="Ver su kardex"><i class="fas fa-file-invoice"></i></a>@endif
                    @if($puedeAjustar)<a href="{{ route('materia-prima.ajuste', $m) }}" class="btn btn-outline-warning" title="Ajustar stock"><i class="fas fa-balance-scale"></i></a>@endif
                    @if($puedeComprar && $m->nivel !== 'ok')<a href="{{ route('compras.create') }}" class="btn btn-outline-primary" title="Registrar compra"><i class="fas fa-cart-plus"></i></a>@endif
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
    // Búsqueda automática: al dejar de escribir medio segundo, se filtra sola.
    (function () {
        const input = document.getElementById('inBuscar');
        const form  = document.getElementById('formStock');
        let timer;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => form.submit(), 500);
        });
        // Mantiene el cursor al final tras recargar si se estaba escribiendo
        if (input.value) { input.focus(); input.setSelectionRange(input.value.length, input.value.length); }
    })();
</script>
@endpush
