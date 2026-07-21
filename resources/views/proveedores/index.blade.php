@extends('layouts.app')
@section('title', 'Proveedores')
@section('breadcrumb') <li class="breadcrumb-item active">Proveedores</li> @endsection

@push('styles')
<style>
    .prov-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .prov-stat {
        background: #fff; border-radius: 12px; padding: 1rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .8rem;
    }
    body.dark-mode .prov-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .prov-stat .pv-icon {
        width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .prov-stat .pv-value { font-size: 1.35rem; font-weight: 800; color: #1a1a2e; line-height: 1; }
    body.dark-mode .prov-stat .pv-value { color: #f0f0f7; }
    .prov-stat .pv-label { font-size: .74rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .2rem; }
    .prov-stat.total     { border-left-color: #3498db; }
    .prov-stat.total .pv-icon     { background: rgba(52,152,219,.14); color: #2170a3; }
    .prov-stat.activos   { border-left-color: #2ecc71; }
    .prov-stat.activos .pv-icon   { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .prov-stat.inactivos { border-left-color: #adb5bd; }
    .prov-stat.inactivos .pv-icon { background: rgba(173,181,189,.18); color: #6c757d; }
    .prov-stat.compras   { border-left-color: #b5451b; }
    .prov-stat.compras .pv-icon   { background: rgba(181,69,27,.12); color: #b5451b; }

    .prov-contacto { line-height: 1.35; }
    .prov-contacto .pc-persona { font-weight: 600; color: #495057; font-size: .86rem; }
    body.dark-mode .prov-contacto .pc-persona { color: #c8c8d4; }
    .prov-contacto .pc-tel { font-size: .78rem; color: #8a8a9d; }
    body.dark-mode .prov-contacto .pc-tel { color: #9a9ac0; }
    .prov-contacto .pc-vacio { color: #ced4da; font-style: italic; font-size: .82rem; }

    @media (max-width: 900px) { .prov-stats { grid-template-columns: repeat(2, 1fr); } }
</style>
@endpush

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

<div class="prov-stats">
    <div class="prov-stat total">
        <div class="pv-icon"><i class="fas fa-truck"></i></div>
        <div><div class="pv-value">{{ $stats['total'] }}</div><div class="pv-label">Proveedores</div></div>
    </div>
    <div class="prov-stat activos">
        <div class="pv-icon"><i class="fas fa-check-circle"></i></div>
        <div><div class="pv-value">{{ $stats['activos'] }}</div><div class="pv-label">Activos</div></div>
    </div>
    <div class="prov-stat inactivos">
        <div class="pv-icon"><i class="fas fa-ban"></i></div>
        <div><div class="pv-value">{{ $stats['inactivos'] }}</div><div class="pv-label">Inactivos</div></div>
    </div>
    <div class="prov-stat compras">
        <div class="pv-icon"><i class="fas fa-shopping-cart"></i></div>
        <div><div class="pv-value">{{ $stats['compras'] }}</div><div class="pv-label">Compras registradas</div></div>
    </div>
</div>

<form method="GET" action="{{ route('proveedores.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, RUC o contacto..." value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Estado</label>
    <select name="estado" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="activo"   {{ request('estado') === 'activo'   ? 'selected' : '' }}>Activo</option>
        <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('estado'))
        <a href="{{ route('proveedores.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($proveedores->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Proveedor</th>
                <th>RUC</th>
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
                <td>
                    <div class="prov-contacto">
                        @if($p->contacto || $p->telefono)
                            @if($p->contacto)<div class="pc-persona">{{ $p->contacto }}</div>@endif
                            @if($p->telefono)<div class="pc-tel"><i class="fas fa-phone-alt mr-1"></i>{{ $p->telefono }}</div>@endif
                        @else
                            <span class="pc-vacio">Sin datos de contacto</span>
                        @endif
                    </div>
                </td>
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
    <p>No hay proveedores que coincidan con este filtro</p>
    <a href="{{ route('proveedores.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@endsection
