@extends('layouts.app')
@section('title', 'Movimientos de Productos')
@section('breadcrumb') <li class="breadcrumb-item active">Movimientos de Productos</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-book mr-2 text-primary"></i>Movimientos de Productos</h5>
        <a href="{{ route('kardex.rotacion') }}" class="btn btn-success">
            <i class="fas fa-chart-bar mr-1"></i>Rotación de Stock
        </a>
    </div>

    {{-- Filtros --}}
    <div class="card-body border-bottom">
        <form method="GET" class="form-inline flex-wrap gap-2">
            <select name="id_producto" class="form-control mr-2 mb-2">
                <option value="">Todos los productos</option>
                @foreach($productos as $p)
                    <option value="{{ $p->id }}" {{ request('id_producto') == $p->id ? 'selected' : '' }}>
                        {{ $p->nombre }}
                    </option>
                @endforeach
            </select>
            <select name="tipo" class="form-control mr-2 mb-2">
                <option value="">Todos</option>
                <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entradas</option>
                <option value="salida"  {{ request('tipo') == 'salida'  ? 'selected' : '' }}>Salidas</option>
            </select>
            <input type="date" name="fecha_desde" class="form-control mr-2 mb-2" value="{{ request('fecha_desde') }}">
            <input type="date" name="fecha_hasta" class="form-control mr-2 mb-2" value="{{ request('fecha_hasta') }}">
            <button class="btn btn-primary mb-2"><i class="fas fa-search mr-1"></i>Filtrar</button>
            <a href="{{ route('kardex.index') }}" class="btn btn-secondary mb-2">Limpiar</a>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
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
                @forelse($movimientos as $m)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</td>
                    <td><strong>{{ $m->producto->nombre }}</strong></td>
                    <td class="text-center">
                        @if($m->tipo === 'entrada')
                            <span class="badge badge-success"><i class="fas fa-arrow-up mr-1"></i>Entrada</span>
                        @else
                            <span class="badge badge-danger"><i class="fas fa-arrow-down mr-1"></i>Salida</span>
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
                @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No hay movimientos registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $movimientos->links() }}</div>
</div>
@endsection
