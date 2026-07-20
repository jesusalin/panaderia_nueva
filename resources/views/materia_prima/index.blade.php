@extends('layouts.app')
@section('title', 'Materia Prima')
@section('breadcrumb') <li class="breadcrumb-item active">Materia Prima</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-boxes mr-2 text-secondary"></i>Materia Prima</h2>
        <p>Insumos con los que se produce (harina, azúcar, lácteos, etc.)</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('materia-prima.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nueva Materia Prima
        </a>
    </div>
</div>

@if($materias->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Insumo</th>
                <th class="text-center">Unidad</th>
                <th class="text-right">Stock Actual</th>
                <th class="text-right">Stock Mínimo</th>
                <th class="text-right">Costo Unitario</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materias as $m)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="row-icon"><i class="fas fa-wheat-awn"></i></div>
                        <div class="ml-2">
                            <div class="row-title">{{ $m->nombre }}</div>
                            @if($m->tieneStockBajo())
                                <span class="badge-soft badge-soft-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Stock bajo</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="text-center"><span class="badge-soft badge-soft-secondary">{{ $m->unidad->abreviatura }}</span></td>
                <td class="text-right">
                    <span class="font-weight-bold {{ $m->tieneStockBajo() ? 'text-danger' : 'text-success' }}">
                        {{ number_format($m->stock_actual, 3) }}
                    </span>
                </td>
                <td class="text-right text-muted">{{ number_format($m->stock_minimo, 3) }}</td>
                <td class="text-right">S/ {{ number_format($m->costo_unitario, 2) }}</td>
                <td class="text-center">
                    <span class="badge-soft {{ $m->estado === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ucfirst($m->estado) }}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('materia-prima.ajuste', $m) }}" class="btn btn-icon btn-info" title="Ajustar inventario">
                            <i class="fas fa-balance-scale"></i>
                        </a>
                        <a href="{{ route('materia-prima.edit', $m) }}" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('materia-prima.destroy', $m) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Desactivar insumo?"
                            data-confirm="&quot;{{ $m->nombre }}&quot; dejará de estar disponible para recetas y compras.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Desactivar"><i class="fas fa-ban"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $materias->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-boxes"></i>
    <p>Todavía no tienes materia prima registrada</p>
    <a href="{{ route('materia-prima.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear la primera</a>
</div>
@endif

@endsection
