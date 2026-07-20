@extends('layouts.app')
@section('title', 'Proveedores')
@section('breadcrumb') <li class="breadcrumb-item active">Proveedores</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-truck mr-2 text-primary"></i>Proveedores</h2>
        <p>Empresas que abastecen de materia prima a la panadería</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('proveedores.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Proveedor
        </a>
    </div>
</div>

@if($proveedores->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>RUC</th>
                <th>Teléfono</th>
                <th>Contacto</th>
                <th class="text-center">Compras</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($proveedores as $p)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="row-icon"><i class="fas fa-truck"></i></div>
                        <div class="ml-2">
                            <div class="row-title">{{ $p->nombre }}</div>
                            @if($p->email)<div class="row-subtitle">{{ $p->email }}</div>@endif
                        </div>
                    </div>
                </td>
                <td>{{ $p->ruc ?? '—' }}</td>
                <td>{{ $p->telefono ?? '—' }}</td>
                <td>{{ $p->contacto ?? '—' }}</td>
                <td class="text-center"><span class="badge-soft badge-soft-info">{{ $p->compras_count }}</span></td>
                <td class="text-center">
                    <span class="badge-soft {{ $p->estado === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ucfirst($p->estado) }}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('proveedores.edit', $p) }}" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('proveedores.destroy', $p) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Desactivar proveedor?"
                            data-confirm="&quot;{{ $p->nombre }}&quot; ya no aparecerá disponible para nuevas compras.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Desactivar"><i class="fas fa-ban"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $proveedores->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-truck"></i>
    <p>Todavía no tienes proveedores registrados</p>
    <a href="{{ route('proveedores.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@endsection
