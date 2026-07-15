@extends('layouts.app')
@section('title', 'Usuarios')
@section('breadcrumb') <li class="breadcrumb-item active">Usuarios</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-users mr-2 text-dark"></i>Usuarios del Sistema</h5>
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Usuario
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th class="text-center">Rol</th>
                    <th>Módulos asignados</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $u)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="mr-2 rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center"
                                style="width:35px;height:35px;font-size:.9rem;flex-shrink:0">
                                {{ strtoupper(substr($u->nombre,0,1)) }}
                            </div>
                            <div>
                                <strong>{{ $u->nombre }}</strong>
                                @if($u->apodo)
                                    <br><span class="badge badge-light border">{{ $u->apodo }}</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td><code>{{ $u->usuario }}</code></td>
                    <td>{{ $u->email }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $u->rol->nombre === 'admin' ? 'danger' : 'secondary' }}">{{ ucfirst($u->rol->nombre) }}</span>
                    </td>
                    <td>
                        @if($u->rol->nombre === 'admin')
                            <span class="text-muted small"><i class="fas fa-infinity mr-1"></i>Acceso total</span>
                        @elseif($u->permisos->isEmpty())
                            <span class="text-muted small">Sin módulos asignados</span>
                        @else
                            @foreach($u->permisos as $p)
                                <span class="badge badge-info mb-1">{{ \App\Models\Usuario::MODULOS[$p->modulo] ?? $p->modulo }}</span>
                            @endforeach
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-{{ $u->estado === 'activo' ? 'success' : 'secondary' }}">
                            {{ ucfirst($u->estado) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if(auth()->id() !== $u->id)
                        <form action="{{ route('usuarios.destroy', $u) }}" method="POST" class="d-inline"
                            onsubmit="return confirm('¿Desactivar este usuario?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger"><i class="fas fa-ban"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay usuarios registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $usuarios->links() }}</div>
</div>
@endsection
