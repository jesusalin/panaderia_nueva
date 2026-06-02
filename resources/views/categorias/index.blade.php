{{-- resources/views/categorias/index.blade.php --}}
@extends('layouts.app')
@section('title', 'Categorías')
@section('breadcrumb') <li class="breadcrumb-item active">Categorías</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-tags mr-2 text-info"></i>Categorías</h5>
        <a href="{{ route('categorias.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nueva Categoría
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th class="text-center">Productos</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categorias as $c)
                <tr>
                    <td><strong>{{ $c->nombre }}</strong></td>
                    <td class="text-muted">{{ $c->descripcion ?? '—' }}</td>
                    <td class="text-center"><span class="badge badge-info badge-pill">{{ $c->productos_count }}</span></td>
                    <td class="text-center">
                        <span class="badge badge-{{ $c->estado === 'activo' ? 'success' : 'secondary' }}">
                            {{ ucfirst($c->estado) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('categorias.edit', $c) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('categorias.destroy', $c) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('¿Desactivar esta categoría?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fas fa-ban"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No hay categorías registradas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $categorias->links() }}</div>
</div>
@endsection
