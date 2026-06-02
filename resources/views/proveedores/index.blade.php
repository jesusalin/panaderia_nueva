@extends('layouts.app')
@section('title', 'Proveedores')
@section('breadcrumb') <li class="breadcrumb-item active">Proveedores</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-truck mr-2 text-primary"></i>Proveedores</h5>
        <a href="{{ route('proveedores.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Proveedor
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Nombre</th>
                    <th>RUC</th>
                    <th>Teléfono</th>
                    <th>Contacto</th>
                    <th class="text-center">Compras</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proveedores as $p)
                <tr>
                    <td>
                        <strong>{{ $p->nombre }}</strong>
                        @if($p->email)
                            <br><small class="text-muted">{{ $p->email }}</small>
                        @endif
                    </td>
                    <td>{{ $p->ruc ?? '—' }}</td>
                    <td>{{ $p->telefono ?? '—' }}</td>
                    <td>{{ $p->contacto ?? '—' }}</td>
                    <td class="text-center"><span class="badge badge-info badge-pill">{{ $p->compras_count }}</span></td>
                    <td class="text-center">
                        <span class="badge badge-{{ $p->estado === 'activo' ? 'success' : 'secondary' }}">
                            {{ ucfirst($p->estado) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('proveedores.edit', $p) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('proveedores.destroy', $p) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('¿Desactivar este proveedor?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fas fa-ban"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay proveedores registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $proveedores->links() }}</div>
</div>
@endsection
