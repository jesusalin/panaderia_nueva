@extends('layouts.app')
@section('title', 'Usuarios')
@section('breadcrumb') <li class="breadcrumb-item active">Usuarios</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-users mr-2 text-dark"></i>Usuarios del Sistema</h2>
        <p>Cuentas de acceso y módulos asignados a cada persona</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Usuario
        </a>
    </div>
</div>

@if($usuarios->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
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
            @foreach($usuarios as $u)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="row-icon" style="background:linear-gradient(135deg,#4a4a5e,#1a1a2e);">
                            {{ strtoupper(substr($u->nombre,0,1)) }}
                        </div>
                        <div class="ml-2">
                            <div class="row-title">{{ $u->nombre }}</div>
                            @if($u->apodo)<span class="badge-soft badge-soft-secondary">{{ $u->apodo }}</span>@endif
                        </div>
                    </div>
                </td>
                <td><code>{{ $u->usuario }}</code></td>
                <td>{{ $u->email }}</td>
                <td class="text-center">
                    <span class="badge-soft {{ $u->rol->nombre === 'admin' ? 'badge-soft-danger' : 'badge-soft-secondary' }}">{{ ucfirst($u->rol->nombre) }}</span>
                </td>
                <td>
                    @if($u->rol->nombre === 'admin')
                        <span class="text-muted small"><i class="fas fa-infinity mr-1"></i>Acceso total</span>
                    @elseif($u->permisos->isEmpty())
                        <span class="text-muted small">Sin módulos asignados</span>
                    @else
                        @foreach($u->permisos as $p)
                            <span class="badge-soft badge-soft-info mr-1">{{ \App\Models\Usuario::MODULOS[$p->modulo] ?? $p->modulo }}</span>
                        @endforeach
                    @endif
                </td>
                <td class="text-center">
                    <span class="badge-soft {{ $u->estado === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ucfirst($u->estado) }}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if(auth()->id() !== $u->id)
                        <form action="{{ route('usuarios.destroy', $u) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Desactivar usuario?"
                            data-confirm="&quot;{{ $u->nombre }}&quot; ya no podrá iniciar sesión en el sistema.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Desactivar"><i class="fas fa-ban"></i></button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $usuarios->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-users"></i>
    <p>No hay usuarios registrados</p>
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@endsection
