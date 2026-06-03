@extends('layouts.app')
@section('title', 'Clientes')
@section('breadcrumb') <li class="breadcrumb-item active">Clientes</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-users mr-2 text-success"></i>Clientes y Puntos de Distribución</h5>
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Cliente
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Distrito</th>
                    <th>Teléfono</th>
                    <th class="text-center">Ventas</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clientes as $cliente)
                <tr>
                    <td>
                        <strong>{{ $cliente->nombre }}</strong>
                        @if($cliente->email)
                            <br><small class="text-muted">{{ $cliente->email }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-info badge-pill">
                            {{ ucfirst($cliente->tipo ?? 'particular') }}
                        </span>
                    </td>
                    <td>{{ $cliente->distrito ?? '—' }}</td>
                    <td>{{ $cliente->telefono ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge badge-info badge-pill">{{ $cliente->ventas_count }}</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ ($cliente->estado ?? 'activo') === 'activo' ? 'success' : 'secondary' }}">
                            {{ ucfirst($cliente->estado ?? 'activo') }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($cliente->nombre !== 'Cliente General')
                        <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Desactivar a {{ $cliente->nombre }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">
                                <i class="fas fa-ban"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay clientes registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $clientes->links() }}</div>
</div>
@endsection
