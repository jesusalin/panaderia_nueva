@extends('layouts.app')
@section('title', 'Rotación de Stock')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('kardex.index') }}">Movimientos de Productos</a></li>
    <li class="breadcrumb-item active">Rotación de Stock</li>
@endsection
 
@section('content')
 
{{-- Filtro por mes --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="form-inline">
            <label class="mr-2">Mes:</label>
            <select name="mes" class="form-control mr-2">
                @foreach($meses as $num => $nombre)
                    <option value="{{ $num }}" {{ $mes == $num ? 'selected' : '' }}>{{ $nombre }}</option>
                @endforeach
            </select>
            <label class="mr-2">Año:</label>
            <input type="number" name="año" class="form-control mr-2" value="{{ $año }}" min="2024" max="2030" style="width:90px">
            <button class="btn btn-primary"><i class="fas fa-search mr-1"></i>Consultar</button>
        </form>
    </div>
</div>
 
{{-- Resumen --}}
@php
    $totalVendido  = $rotacion->sum('total_vendido');
    $totalIngresos = $rotacion->sum('total_ingresos');
    $totalUtilidad = $rotacion->sum('utilidad');
@endphp
 
<div class="row mb-3">
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($totalVendido) }}</h3>
                <p>Unidades Vendidas</p>
            </div>
            <div class="icon"><i class="fas fa-box-open"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>S/ {{ number_format($totalIngresos, 2) }}</h3>
                <p>Total Ingresos</p>
            </div>
            <div class="icon"><i class="fas fa-cash-register"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>S/ {{ number_format($totalUtilidad, 2) }}</h3>
                <p>Utilidad Bruta</p>
            </div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
        </div>
    </div>
</div>
 
{{-- Tabla de rotación --}}
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="fas fa-chart-bar mr-2 text-success"></i>
            Rotación de Stock — {{ $meses[$mes] }} {{ $año }}
        </h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th class="text-center">Vendido</th>
                    <th class="text-right">Ingresos</th>
                    <th class="text-right">Utilidad</th>
                    <th class="text-center">Margen %</th>
                    <th class="text-center">Stock Actual</th>
                    <th class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rotacion as $i => $p)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $p->nombre }}</strong></td>
                    <td><span class="badge badge-info badge-pill">{{ $p->categoria }}</span></td>
                    <td class="text-center font-weight-bold">{{ number_format($p->total_vendido) }} uds</td>
                    <td class="text-right">S/ {{ number_format($p->total_ingresos, 2) }}</td>
                    <td class="text-right text-success font-weight-bold">S/ {{ number_format($p->utilidad, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $p->margen >= 50 ? 'success' : ($p->margen >= 20 ? 'warning' : 'danger') }}">
                            {{ $p->margen }}%
                        </span>
                    </td>
                    <td class="text-center">{{ $p->stock_actual }}</td>
                    <td class="text-center">
                        @if($p->stock_actual <= $p->stock_minimo)
                            <span class="badge badge-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Stock bajo</span>
                        @else
                            <span class="badge badge-success">OK</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">Sin ventas en este período</td></tr>
                @endforelse
            </tbody>
            @if($rotacion->count() > 0)
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="3">TOTAL</td>
                    <td class="text-center">{{ number_format($totalVendido) }} uds</td>
                    <td class="text-right">S/ {{ number_format($totalIngresos, 2) }}</td>
                    <td class="text-right text-success">S/ {{ number_format($totalUtilidad, 2) }}</td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
@endsection