@extends('layouts.app')
@section('title', 'Movimientos de Productos')
@section('breadcrumb') <li class="breadcrumb-item active">Movimientos de Productos</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-book mr-2 text-primary"></i>Movimientos de Productos</h2>
        <p>Historial de entradas y salidas de productos terminados (Kardex)</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('kardex.rotacion') }}" class="btn btn-success">
            <i class="fas fa-chart-bar mr-1"></i>Rotación de Stock
        </a>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" class="form-inline flex-wrap" style="gap:.6rem" onsubmit="TiempoOperacion.marcarInicio('verificacion_stock')">
        <span class="fb-label"><i class="fas fa-filter mr-1"></i>Filtrar</span>
        <select name="id_producto" class="form-control">
            <option value="">Todos los productos</option>
            @foreach($productos as $p)
                <option value="{{ $p->id }}" {{ request('id_producto') == $p->id ? 'selected' : '' }}>
                    {{ $p->nombre }}
                </option>
            @endforeach
        </select>
        <select name="tipo" class="form-control">
            <option value="">Todos los tipos</option>
            <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entradas</option>
            <option value="salida"  {{ request('tipo') == 'salida'  ? 'selected' : '' }}>Salidas</option>
        </select>
        <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}">
        <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}">
        <button class="btn btn-primary"><i class="fas fa-search mr-1"></i>Filtrar</button>
        <a href="{{ route('kardex.index') }}" class="btn btn-light">Limpiar</a>
    </form>
</div>

@if($movimientos->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th class="text-center">Tipo</th>
                <th>Motivo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Stock Antes</th>
                <th class="text-center">Stock Después</th>
                <th>Usuario</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $m)
            <tr>
                <td class="text-muted">{{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</td>
                <td class="row-title">{{ $m->producto->nombre }}</td>
                <td class="text-center">
                    @if($m->tipo === 'entrada')
                        <span class="badge-soft badge-soft-success"><i class="fas fa-arrow-up mr-1"></i>Entrada</span>
                    @else
                        <span class="badge-soft badge-soft-danger"><i class="fas fa-arrow-down mr-1"></i>Salida</span>
                    @endif
                </td>
                <td>{{ ucfirst(str_replace('_',' ',$m->motivo)) }}</td>
                <td class="text-center font-weight-bold {{ $m->tipo === 'entrada' ? 'text-success' : 'text-danger' }}">
                    {{ $m->tipo === 'entrada' ? '+' : '-' }}{{ $m->cantidad }}
                </td>
                <td class="text-center">{{ $m->stock_antes }}</td>
                <td class="text-center font-weight-bold">{{ $m->stock_despues }}</td>
                <td>{{ $m->usuario->nombre ?? '—' }}</td>
                <td><small class="text-muted">{{ $m->observacion ?? '—' }}</small></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $movimientos->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-book"></i>
    <p>No hay movimientos registrados con este filtro</p>
</div>
@endif

@endsection

@push('scripts')
<script>
// Si venimos de aplicar un filtro (búsqueda de stock), registrar el tiempo que tomó
TiempoOperacion.registrarFin('verificacion_stock');
</script>
@endpush
