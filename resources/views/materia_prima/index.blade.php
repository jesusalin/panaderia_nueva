@extends('layouts.app')
@section('title', 'Materia Prima')
@section('breadcrumb') <li class="breadcrumb-item active">Materia Prima</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-boxes mr-2 text-secondary"></i>Materia Prima</h5>
        <a href="{{ route('materia-prima.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nueva Materia Prima
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Nombre</th>
                    <th class="text-center">Unidad</th>
                    <th class="text-right">Stock Actual</th>
                    <th class="text-right">Stock Mínimo</th>
                    <th class="text-right">Costo Unitario</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materias as $m)
                <tr>
                    <td>
                        <strong>{{ $m->nombre }}</strong>
                        @if($m->tieneStockBajo())
                            <span class="badge badge-danger ml-1"><i class="fas fa-exclamation-triangle"></i> Stock bajo</span>
                        @endif
                    </td>
                    <td class="text-center"><span class="badge badge-light">{{ $m->unidad->abreviatura }}</span></td>
                    <td class="text-right">
                        <span class="font-weight-bold {{ $m->tieneStockBajo() ? 'text-danger' : 'text-success' }}">
                            {{ number_format($m->stock_actual, 3) }}
                        </span>
                    </td>
                    <td class="text-right text-muted">{{ number_format($m->stock_minimo, 3) }}</td>
                    <td class="text-right">S/ {{ number_format($m->costo_unitario, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $m->estado === 'activo' ? 'success' : 'secondary' }}">
                            {{ ucfirst($m->estado) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('materia-prima.ajuste', $m) }}" class="btn btn-sm btn-info" title="Ajustar inventario">
                            <i class="fas fa-balance-scale"></i>
                        </a>
                        <a href="{{ route('materia-prima.edit', $m) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('materia-prima.destroy', $m) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('¿Desactivar este ingrediente?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fas fa-ban"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay materia prima registrada</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $materias->links() }}</div>
</div>
@endsection
