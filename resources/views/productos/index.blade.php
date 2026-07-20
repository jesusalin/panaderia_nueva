@extends('layouts.app')
@section('title', 'Productos')
@section('breadcrumb') <li class="breadcrumb-item active">Productos</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-bread-slice mr-2 text-warning"></i>Productos</h2>
        <p>Catálogo de productos terminados que la panadería fabrica y vende</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('productos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Producto
        </a>
    </div>
</div>

@if($productos->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoría</th>
                <th class="text-right">Precio Venta</th>
                <th class="text-right">Costo</th>
                <th class="text-center">Stock</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $p)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="row-icon"><i class="fas fa-bread-slice"></i></div>
                        <div class="ml-2">
                            <div class="row-title">{{ $p->nombre }}</div>
                            @if($p->tieneStockBajo())
                                <span class="badge-soft badge-soft-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Stock bajo</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td><span class="badge-soft badge-soft-secondary">{{ $p->categoria->nombre }}</span></td>
                <td class="text-right font-weight-bold text-success">S/ {{ number_format($p->precio_venta, 2) }}</td>
                <td class="text-right text-muted">S/ {{ number_format($p->costo_produccion, 2) }}</td>
                <td class="text-center">
                    <span class="badge-soft {{ $p->tieneStockBajo() ? 'badge-soft-danger' : 'badge-soft-success' }}">
                        {{ $p->stock_actual }}
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge-soft {{ $p->estado === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ucfirst($p->estado) }}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('productos.edit', $p) }}" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('productos.destroy', $p) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Desactivar producto?"
                            data-confirm="&quot;{{ $p->nombre }}&quot; dejará de aparecer disponible para la venta.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Desactivar"><i class="fas fa-ban"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $productos->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-bread-slice"></i>
    <p>Todavía no tienes productos registrados</p>
    <a href="{{ route('productos.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@endsection
