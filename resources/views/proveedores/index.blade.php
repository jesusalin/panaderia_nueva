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
    @media (max-width: 900px) { .prov-stats { grid-template-columns: repeat(2, 1fr); } }

    .prov-card-body { padding: 1rem 1.1rem .2rem; flex: 1; }
    .prov-card-row { display: flex; align-items: center; gap: .5rem; font-size: .83rem; color: #6c757d; padding: .28rem 0; }
    .prov-card-row i { width: 16px; color: #adb5bd; }
    body.dark-mode .prov-card-row { color: #b0b0cc; }
    .prov-card-row.vacio { color: #ced4da; font-style: italic; }
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
<div class="card-grid">
    @foreach($proveedores as $p)
        @php
            $usosProveedor = [];
            if ($p->compras_count > 0)            $usosProveedor[] = $p->compras_count . ' compra(s)';
            if ($p->ordenes_automaticas_count > 0) $usosProveedor[] = 'órdenes automáticas asociadas';
            $tieneHistorial = count($usosProveedor) > 0;
            $estaActivo = $p->estado === 'activo';
        @endphp
        <div class="item-card {{ !$estaActivo ? 'is-inactive' : '' }}">
            <div class="item-card-body prov-card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="row-icon" style="margin-bottom:.6rem;"><i class="fas fa-truck"></i></div>
                </div>
                <div class="item-card-cat">{{ $p->ruc ?? 'Sin RUC registrado' }}</div>
                <h3 class="item-card-title">{{ $p->nombre }}</h3>

                <div class="mt-2">
                    @if($p->contacto)
                        <div class="prov-card-row"><i class="fas fa-user"></i>{{ $p->contacto }}</div>
                    @endif
                    @if($p->telefono)
                        <div class="prov-card-row"><i class="fas fa-phone-alt"></i>{{ $p->telefono }}</div>
                    @endif
                    @if($p->email)
                        <div class="prov-card-row"><i class="fas fa-envelope"></i>{{ $p->email }}</div>
                    @endif
                    @if(!$p->contacto && !$p->telefono && !$p->email)
                        <div class="prov-card-row vacio">Sin datos de contacto</div>
                    @endif
                </div>

                <div class="item-card-stockrow mt-2">
                    <span>Compras registradas</span>
                    <span class="badge-soft badge-soft-info">{{ $p->compras_count }}</span>
                </div>
            </div>

            <div class="item-card-footer">
                <form action="{{ route('proveedores.toggle-estado', $p) }}" method="POST">
                    @csrf @method('PUT')
                    <button type="submit" class="estado-switch {{ $estaActivo ? 'activa' : '' }}">
                        <span class="track"></span>
                        <span class="txt">{{ $estaActivo ? 'Activo' : 'Inactivo' }}</span>
                    </button>
                </form>
                <div class="btn-icon-group">
                    <a href="{{ route('proveedores.edit', $p) }}" class="btn btn-icon btn-warning" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    @if($estaActivo)
                        <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                            data-blocked-title="Desactívalo primero"
                            data-blocked-message="Por seguridad, un proveedor activo no se puede eliminar directamente. Usa el interruptor para desactivar a &quot;{{ $p->nombre }}&quot; antes de eliminarlo, así evitamos borrados por error.">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @elseif($tieneHistorial)
                        <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                            data-blocked-title="No se puede eliminar este proveedor"
                            data-blocked-message="&quot;{{ $p->nombre }}&quot; {{ implode(' y ', $usosProveedor) }}. Permanecerá desactivado para conservar el historial.">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @else
                        <form action="{{ route('proveedores.destroy', $p) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Eliminar este proveedor?"
                            data-confirm="&quot;{{ $p->nombre }}&quot; se borrará por completo del sistema. Esta acción NO se puede deshacer.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-3">{{ $proveedores->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-truck"></i>
    <p>No hay proveedores que coincidan con este filtro</p>
    <a href="{{ route('proveedores.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@endsection
