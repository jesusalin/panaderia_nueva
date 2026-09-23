@extends('layouts.app')
@section('title', 'Reporte de Kardex')
@section('breadcrumb') <li class="breadcrumb-item active">Reporte de Kardex</li> @endsection

@push('styles')
<style>
    .rp-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .rp-stat { background: #fff; border-radius: 14px; padding: 1.1rem 1.3rem; box-shadow: 0 2px 12px rgba(0,0,0,.05); border-left: 4px solid #dee2e6; }
    .rp-stat.acc-info    { border-left-color: #3498db; }
    .rp-stat.acc-success { border-left-color: #2ecc71; }
    .rp-stat.acc-danger  { border-left-color: #e74c3c; }
    .rp-stat .rp-label { font-size: .74rem; font-weight: 700; color: #8a8a9d; text-transform: uppercase; letter-spacing: .03em; }
    .rp-stat .rp-value { font-size: 1.4rem; font-weight: 800; color: #1a1a2e; margin-top: .2rem; }
    body.dark-mode .rp-stat { background: #1f1f33; }
    body.dark-mode .rp-stat .rp-value { color: #f0f0f7; }

    .rp-filter { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1rem 1.3rem; margin-bottom: 1.3rem; }
    .rp-filter .form-control { border-radius: 10px; border: 1.5px solid #e9ecef; }
    body.dark-mode .rp-filter .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-file-invoice mr-2 text-info"></i>Reporte de Kardex</h2>
        <p>Ficha cronológica de entradas, salidas y saldo de un insumo o producto</p>
    </div>
    @if($item)
    <div class="toolbar-actions">
        <a href="{{ route('reportes.kardex.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-secondary">
            <i class="fas fa-file-pdf mr-1"></i>PDF
        </a>
        <a href="{{ route('reportes.kardex.excel', request()->query()) }}" class="btn btn-outline-success">
            <i class="fas fa-file-excel mr-1"></i>Excel
        </a>
    </div>
    @endif
</div>

<div class="card rp-filter">
    <form method="GET" class="form-inline flex-wrap" style="gap:.6rem;">
        <select name="tipo_item" id="selTipoItem" class="form-control" onchange="cambiarTipoItem()">
            <option value="">-- Tipo de ítem --</option>
            <option value="materia"  {{ $tipoItem === 'materia'  ? 'selected' : '' }}>Materia Prima</option>
            <option value="producto" {{ $tipoItem === 'producto' ? 'selected' : '' }}>Producto</option>
        </select>

        <select name="id_item" id="selMateria" class="form-control"
            style="display:{{ $tipoItem === 'materia' ? 'inline-block' : 'none' }};"
            {{ $tipoItem !== 'materia' ? 'disabled' : '' }}>
            <option value="">-- Seleccionar insumo --</option>
            @foreach($materias as $m)
                <option value="{{ $m->id }}" {{ $idItem == $m->id && $tipoItem === 'materia' ? 'selected' : '' }}>{{ $m->nombre }}</option>
            @endforeach
        </select>

        <select name="id_item" id="selProducto" class="form-control"
            style="display:{{ $tipoItem === 'producto' ? 'inline-block' : 'none' }};"
            {{ $tipoItem !== 'producto' ? 'disabled' : '' }}>
            <option value="">-- Seleccionar producto --</option>
            @foreach($productos as $p)
                <option value="{{ $p->id }}" {{ $idItem == $p->id && $tipoItem === 'producto' ? 'selected' : '' }}>{{ $p->nombre }}</option>
            @endforeach
        </select>

        <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}" title="Desde">
        <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}" title="Hasta">

        <button class="btn btn-primary"><i class="fas fa-search mr-1"></i>Ver Kardex</button>
    </form>
</div>

@if($item)
    @php
        $totalEntradas = $movimientos->where('tipo', 'entrada')->sum('cantidad');
        $totalSalidas  = $movimientos->where('tipo', 'salida')->sum('cantidad');
        $saldoActual   = $item->stock_actual;
    @endphp

    <div class="rp-stat-grid">
        <div class="rp-stat acc-info">
            <div class="rp-label">Ítem</div>
            <div class="rp-value" style="font-size:1.05rem;">{{ $item->nombre }}</div>
        </div>
        <div class="rp-stat acc-success">
            <div class="rp-label">Total Entradas (periodo)</div>
            <div class="rp-value">{{ number_format($totalEntradas, 2) }} {{ $unidad }}</div>
        </div>
        <div class="rp-stat acc-danger">
            <div class="rp-label">Total Salidas (periodo)</div>
            <div class="rp-value">{{ number_format($totalSalidas, 2) }} {{ $unidad }}</div>
        </div>
        <div class="rp-stat">
            <div class="rp-label">Saldo Actual</div>
            <div class="rp-value">{{ number_format($saldoActual, 2) }} {{ $unidad }}</div>
        </div>
    </div>

    @if($movimientos->count() > 0)
    <div class="table-card">
        <table class="table table-modern mb-0">
            <thead>
                <tr>
                    <th>Fecha</th><th>Tipo</th><th>Motivo</th>
                    <th class="text-right">Entrada</th><th class="text-right">Salida</th>
                    <th class="text-right">Saldo</th><th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movimientos as $mov)
                <tr>
                    <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <span class="badge-soft {{ $mov->tipo === 'entrada' ? 'badge-soft-success' : ($mov->tipo === 'salida' ? 'badge-soft-danger' : 'badge-soft-secondary') }}">
                            {{ ucfirst($mov->tipo) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ ucfirst(str_replace('_', ' ', $mov->motivo)) }}</td>
                    <td class="text-right text-success font-weight-bold">{{ $mov->tipo === 'entrada' ? number_format($mov->cantidad, 2) : '' }}</td>
                    <td class="text-right text-danger font-weight-bold">{{ $mov->tipo === 'salida' ? number_format($mov->cantidad, 2) : '' }}</td>
                    <td class="text-right font-weight-bold">{{ number_format($mov->stock_despues, 2) }} {{ $unidad }}</td>
                    <td class="text-muted small">{{ $mov->usuario->apodo ?? $mov->usuario->nombre ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-state">
        <i class="fas fa-file-invoice"></i>
        <p>Este ítem no tiene movimientos registrados en el periodo seleccionado.</p>
    </div>
    @endif
@else
<div class="empty-state">
    <i class="fas fa-hand-pointer"></i>
    <p>Selecciona un insumo o producto arriba para ver su ficha kardex.</p>
</div>
@endif

@endsection

@push('scripts')
<script>
    function cambiarTipoItem() {
        const tipo = document.getElementById('selTipoItem').value;
        const selMateria = document.getElementById('selMateria');
        const selProducto = document.getElementById('selProducto');

        selMateria.style.display  = tipo === 'materia'  ? 'inline-block' : 'none';
        selProducto.style.display = tipo === 'producto' ? 'inline-block' : 'none';
        selMateria.disabled  = tipo !== 'materia';
        selProducto.disabled = tipo !== 'producto';

        if (tipo !== 'materia')  selMateria.selectedIndex = 0;
        if (tipo !== 'producto') selProducto.selectedIndex = 0;
    }
</script>
@endpush
