@extends('layouts.app')
@section('title', 'Rotación de Stock')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('kardex.index') }}">Movimientos de Productos</a></li>
    <li class="breadcrumb-item active">Rotación de Stock</li>
@endsection

@push('styles')
<style>
    .rot-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .rot-stat {
        background: #fff; border-radius: 12px; padding: 1.1rem 1.25rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .9rem;
    }
    body.dark-mode .rot-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .rot-stat .rs-icon {
        width: 46px; height: 46px; border-radius: 11px; display: flex; align-items: center; justify-content: center;
        font-size: 1.15rem; flex-shrink: 0;
    }
    .rot-stat .rs-value { font-size: 1.5rem; font-weight: 800; color: #1a1a2e; line-height: 1.1; }
    body.dark-mode .rot-stat .rs-value { color: #f0f0f7; }
    .rot-stat .rs-label { font-size: .76rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .25rem; }
    .rot-stat.vendido  { border-left-color: #2ecc71; }
    .rot-stat.vendido .rs-icon { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .rot-stat.ingresos { border-left-color: #3498db; }
    .rot-stat.ingresos .rs-icon { background: rgba(52,152,219,.14); color: #2170a3; }
    .rot-stat.utilidad { border-left-color: #f39c12; }
    .rot-stat.utilidad .rs-icon { background: rgba(243,156,18,.14); color: #b9770e; }

    .rot-rank {
        width: 26px; height: 26px; border-radius: 50%; background: #f1f1f4; color: #8a8a9d;
        display: inline-flex; align-items: center; justify-content: center; font-size: .76rem; font-weight: 800;
    }
    body.dark-mode .rot-rank { background: #24243b; color: #9a9ac0; }
    .rot-rank.top1 { background: #fff4d6; color: #b9860b; }
    .rot-rank.top2 { background: #eef1f5; color: #6c7a89; }
    .rot-rank.top3 { background: #fbe7d9; color: #b5591c; }
    body.dark-mode .rot-rank.top1 { background: rgba(243,156,18,.22); color: #ffc673; }
    body.dark-mode .rot-rank.top2 { background: rgba(148,163,184,.22); color: #cbd5e1; }
    body.dark-mode .rot-rank.top3 { background: rgba(181,69,27,.28); color: #ff9d6e; }

    .table-modern .rot-producto { display: flex; align-items: center; gap: .6rem; }
    .table-modern .rot-producto-icon {
        width: 32px; height: 32px; border-radius: 8px; background: #f7f5f3; color: #b5451b;
        display: flex; align-items: center; justify-content: center; font-size: .82rem; flex-shrink: 0;
        overflow: hidden;
    }
    .table-modern .rot-producto-icon img { width: 100%; height: 100%; object-fit: cover; }
    body.dark-mode .table-modern .rot-producto-icon { background: #24243b; color: #ff9d6e; }
    .rot-producto-cat { display: block; font-size: .72rem; color: #adb5bd; font-weight: 400; }

    .rot-margen-wrap { display: flex; align-items: center; gap: .5rem; justify-content: center; }
    .rot-margen-bar { width: 54px; height: 6px; border-radius: 4px; background: #eef0f3; overflow: hidden; }
    body.dark-mode .rot-margen-bar { background: #33334d; }
    .rot-margen-bar span { display: block; height: 100%; border-radius: 4px; }

    .rot-total-row td { background: #f7f5f3; font-weight: 800; border-top: 2px solid #eee; }
    body.dark-mode .rot-total-row td { background: #24243b; border-top-color: #33334d; color: #f0f0f7; }

    @media (max-width: 768px) { .rot-stats { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-chart-bar mr-2 text-success"></i>Rotación de Stock</h2>
        <p>Desempeño de ventas y utilidad por producto — {{ $meses[$mes] }} {{ $año }}</p>
    </div>
</div>

<form method="GET" class="filter-bar flex-wrap" style="gap:.6rem">
    <span class="fb-label"><i class="fas fa-calendar mr-1"></i>Período</span>
    <select name="mes" class="form-control" style="width:auto;" onchange="this.form.submit()">
        @foreach($meses as $num => $nombre)
            <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nombre }}</option>
        @endforeach
    </select>
    <input type="number" name="año" class="form-control" value="{{ $año }}" min="2024" max="2030" style="width:100px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i>Consultar</button>
</form>

@php
    $totalVendido  = $rotacion->sum('total_vendido');
    $totalIngresos = $rotacion->sum('total_ingresos');
    $totalUtilidad = $rotacion->sum('utilidad');
@endphp

<div class="rot-stats">
    <div class="rot-stat vendido">
        <div class="rs-icon"><i class="fas fa-box-open"></i></div>
        <div><div class="rs-value">{{ number_format($totalVendido) }}</div><div class="rs-label">Unidades vendidas</div></div>
    </div>
    <div class="rot-stat ingresos">
        <div class="rs-icon"><i class="fas fa-cash-register"></i></div>
        <div><div class="rs-value">S/ {{ number_format($totalIngresos, 2) }}</div><div class="rs-label">Total ingresos</div></div>
    </div>
    <div class="rot-stat utilidad">
        <div class="rs-icon"><i class="fas fa-chart-line"></i></div>
        <div><div class="rs-value">S/ {{ number_format($totalUtilidad, 2) }}</div><div class="rs-label">Utilidad bruta</div></div>
    </div>
</div>

@if($porCategoria->count() > 0)
<div class="table-card mb-3">
    <div class="card-header bg-white" style="font-weight:800; font-size:.95rem;">
        <i class="fas fa-layer-group mr-2 text-info"></i>Rotación por Categoría
    </div>
    <table class="table table-hover table-modern mb-0">
        <thead>
            <tr>
                <th>Categoría</th>
                <th class="text-center">Productos</th>
                <th class="text-center">Vendido</th>
                <th class="text-right">Ingresos</th>
                <th class="text-right">Utilidad</th>
                <th style="width:200px;">Participación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($porCategoria as $cat)
            @php
                $catLower = mb_strtolower($cat->categoria ?? '');
                $iconoCat = 'fa-bread-slice';
                if (str_contains($catLower, 'pastel') || str_contains($catLower, 'torta')) $iconoCat = 'fa-birthday-cake';
                elseif (str_contains($catLower, 'galleta'))  $iconoCat = 'fa-cookie';
                elseif (str_contains($catLower, 'empanada')) $iconoCat = 'fa-cheese';
                elseif (str_contains($catLower, 'bebida') || str_contains($catLower, 'café')) $iconoCat = 'fa-mug-hot';
            @endphp
            <tr>
                <td class="row-title">
                    <div class="rot-producto">
                        <div class="rot-producto-icon"><i class="fas {{ $iconoCat }}"></i></div>
                        <div>{{ $cat->categoria ?? 'Sin categoría' }}</div>
                    </div>
                </td>
                <td class="text-center">{{ $cat->productos }}</td>
                <td class="text-center font-weight-bold">{{ number_format($cat->vendido) }} <small class="text-muted">uds</small></td>
                <td class="text-right">S/ {{ number_format($cat->ingresos, 2) }}</td>
                <td class="text-right text-success font-weight-bold">S/ {{ number_format($cat->utilidad, 2) }}</td>
                <td>
                    <div class="rot-margen-wrap" style="justify-content:flex-start;">
                        <div class="rot-margen-bar" style="width:120px;"><span style="width:{{ $cat->participacion }}%; background:#b5451b;"></span></div>
                        <span style="color:#b5451b; font-weight:700; font-size:.82rem;">{{ $cat->participacion }}%</span>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($rotacion->count() > 0)
<div class="table-card">
    <div class="card-header bg-white" style="font-weight:800; font-size:.95rem;">
        <i class="fas fa-list mr-2 text-success"></i>Detalle por Producto
    </div>
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th class="text-center">Vendido</th>
                <th class="text-right">Ingresos</th>
                <th class="text-right">Utilidad</th>
                <th class="text-center">Margen</th>
                <th class="text-center">Stock</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rotacion as $i => $p)
            @php
                $rankClass = $i === 0 ? 'top1' : ($i === 1 ? 'top2' : ($i === 2 ? 'top3' : ''));
                $margenColor = $p->margen >= 50 ? '#2ecc71' : ($p->margen >= 20 ? '#f39c12' : '#e74c3c');
                $stockBajo = $p->stock_actual <= $p->stock_minimo;

                $nombreCatLower = mb_strtolower($p->categoria ?? '');
                $iconoProducto = 'fa-bread-slice';
                if (str_contains($nombreCatLower, 'pastel') || str_contains($nombreCatLower, 'torta')) $iconoProducto = 'fa-birthday-cake';
                elseif (str_contains($nombreCatLower, 'galleta'))  $iconoProducto = 'fa-cookie';
                elseif (str_contains($nombreCatLower, 'empanada')) $iconoProducto = 'fa-cheese';
                elseif (str_contains($nombreCatLower, 'bebida') || str_contains($nombreCatLower, 'café')) $iconoProducto = 'fa-mug-hot';
            @endphp
            <tr>
                <td><span class="rot-rank {{ $rankClass }}">{{ $i + 1 }}</span></td>
                <td class="row-title">
                    <div class="rot-producto">
                        <div class="rot-producto-icon">
                            @if($p->imagen)
                                <img src="{{ asset('storage/'.$p->imagen) }}" alt="{{ $p->nombre }}">
                            @else
                                <i class="fas {{ $iconoProducto }}"></i>
                            @endif
                        </div>
                        <div>
                            {{ $p->nombre }}
                            <span class="rot-producto-cat">{{ $p->categoria }}</span>
                        </div>
                    </div>
                </td>
                <td class="text-center font-weight-bold">{{ number_format($p->total_vendido) }} <small class="text-muted">uds</small></td>
                <td class="text-right">S/ {{ number_format($p->total_ingresos, 2) }}</td>
                <td class="text-right text-success font-weight-bold">S/ {{ number_format($p->utilidad, 2) }}</td>
                <td class="text-center">
                    <div class="rot-margen-wrap">
                        <div class="rot-margen-bar"><span style="width:{{ min($p->margen,100) }}%; background:{{ $margenColor }};"></span></div>
                        <span style="color:{{ $margenColor }}; font-weight:700; font-size:.82rem;">{{ $p->margen }}%</span>
                    </div>
                </td>
                <td class="text-center">
                    <span class="badge-soft {{ $stockBajo ? 'badge-soft-danger' : 'badge-soft-success' }}">
                        @if($stockBajo)<i class="fas fa-exclamation-triangle mr-1"></i>@endif
                        {{ $p->stock_actual }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="rot-total-row">
                <td colspan="2">TOTAL</td>
                <td class="text-center">{{ number_format($totalVendido) }} uds</td>
                <td class="text-right">S/ {{ number_format($totalIngresos, 2) }}</td>
                <td class="text-right">S/ {{ number_format($totalUtilidad, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
@else
<div class="empty-state">
    <i class="fas fa-chart-bar"></i>
    <p>Sin ventas registradas en este período</p>
</div>
@endif

@endsection
