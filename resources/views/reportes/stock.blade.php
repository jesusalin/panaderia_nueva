@extends('layouts.app')
@section('title', 'Reporte de Stock')
@section('breadcrumb') <li class="breadcrumb-item active">Reporte de Stock</li> @endsection

@push('styles')
<style>
    .rp-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
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
</style>
@endpush

@section('content')

@php
    $totalItems = $materias->count() + $productos->count();
    $totalStockBajo = $materias->filter(fn($m) => $m->tieneStockBajo())->count()
                     + $productos->filter(fn($p) => $p->tieneStockBajo())->count();
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-warehouse mr-2 text-info"></i>Reporte de Stock</h2>
        <p>Valorización del inventario: materia prima y productos terminados</p>
    </div>
    <div class="toolbar-actions">
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
        <div class="rp-label">Con stock bajo</div>
        <div class="rp-value">{{ $totalStockBajo }}</div>
    </div>
    <div class="rp-stat acc-warn">
        <div class="rp-label">Valor Materia Prima</div>
        <div class="rp-value">S/ {{ number_format($valorMateria, 2) }}</div>
    </div>
    <div class="rp-stat acc-success">
        <div class="rp-label">Valor Productos</div>
        <div class="rp-value">S/ {{ number_format($valorProducto, 2) }}</div>
    </div>
</div>

<div class="card rp-filter">
    <form method="GET" class="form-inline flex-wrap" style="gap:.6rem;">
        <input type="text" name="buscar" class="form-control" placeholder="Buscar..." value="{{ request('buscar') }}">
        <select name="tipo" class="form-control" onchange="this.form.submit()">
            <option value="todos"    {{ $tipo === 'todos'    ? 'selected' : '' }}>Todos</option>
            <option value="materia"  {{ $tipo === 'materia'  ? 'selected' : '' }}>Solo Materia Prima</option>
            <option value="producto" {{ $tipo === 'producto' ? 'selected' : '' }}>Solo Productos</option>
        </select>
        <select name="stock" class="form-control" onchange="this.form.submit()">
            <option value="">Todo el stock</option>
            <option value="bajo" {{ request('stock') === 'bajo' ? 'selected' : '' }}>Solo stock bajo</option>
        </select>
        <button class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    </form>
</div>

@if($materias->count() > 0)
<div class="rp-section-title"><i class="fas fa-wheat-awn text-secondary"></i>Materia Prima</div>
<div class="list-rows">
    @foreach($materias as $m)
        <div class="list-row">
            <div class="lr-icon {{ $m->tieneStockBajo() ? 'danger' : '' }}"><i class="fas fa-wheat-awn"></i></div>
            <div class="lr-main">
                <div class="lr-title">{{ $m->nombre }}</div>
                <div class="lr-subtitle">{{ $m->proveedor->nombre ?? 'Sin proveedor' }}</div>
            </div>
            <div class="lr-meta">
                <div class="lm-item"><span class="lm-label">Stock</span><span class="lm-value {{ $m->tieneStockBajo() ? 'text-danger' : '' }}">{{ number_format($m->stock_actual, 2) }} {{ $m->unidad->abreviatura }}</span></div>
                <div class="lm-item"><span class="lm-label">Costo Unit.</span><span class="lm-value">S/ {{ number_format($m->costo_unitario, 2) }}</span></div>
            </div>
            <div class="lr-side">
                <span class="lr-amount">S/ {{ number_format($m->stock_actual * $m->costo_unitario, 2) }}</span>
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
            <div class="lr-icon {{ $p->tieneStockBajo() ? 'danger' : 'success' }}"><i class="fas fa-bread-slice"></i></div>
            <div class="lr-main">
                <div class="lr-title">{{ $p->nombre }}</div>
                <div class="lr-subtitle">{{ $p->categoria->nombre ?? 'Sin categoría' }}</div>
            </div>
            <div class="lr-meta">
                <div class="lm-item"><span class="lm-label">Stock</span><span class="lm-value {{ $p->tieneStockBajo() ? 'text-danger' : '' }}">{{ $p->stock_actual }} uds</span></div>
                <div class="lm-item"><span class="lm-label">Costo Unit.</span><span class="lm-value">S/ {{ number_format($p->costo_produccion ?? 0, 2) }}</span></div>
            </div>
            <div class="lr-side">
                <span class="lr-amount">S/ {{ number_format($p->stock_actual * ($p->costo_produccion ?? 0), 2) }}</span>
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
