@extends('layouts.app')
@section('title', 'Productos')
@section('breadcrumb') <li class="breadcrumb-item active">Productos</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-bread-slice mr-2 text-warning"></i>Productos</h5>
        <a href="{{ route('productos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Producto
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th class="text-right">Precio Venta</th>
                    <th class="text-right">Costo</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($productos as $p)
                <tr>
                    <td>
                        <strong>{{ $p->nombre }}</strong>
                        @if($p->tieneStockBajo())
                            <span class="badge badge-danger ml-1">Stock bajo</span>
                        @endif
                    </td>
                    <td><span class="badge badge-light">{{ $p->categoria->nombre }}</span></td>
                    <td class="text-right font-weight-bold text-success">S/ {{ number_format($p->precio_venta, 2) }}</td>
                    <td class="text-right text-muted">S/ {{ number_format($p->costo_produccion, 2) }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $p->tieneStockBajo() ? 'danger' : 'success' }} badge-pill">
                            {{ $p->stock_actual }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $p->estado === 'activo' ? 'success' : 'secondary' }}">
                            {{ ucfirst($p->estado) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('productos.edit', $p) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('productos.destroy', $p) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('¿Desactivar este producto?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fas fa-ban"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay productos registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $productos->links() }}</div>
</div>
@endsection
