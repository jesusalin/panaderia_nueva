@extends('layouts.app')
@section('title', 'Clientes')
@section('breadcrumb') <li class="breadcrumb-item active">Clientes</li> @endsection

@push('styles')
<style>
    .cli-stat-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 767px) { .cli-stat-row { grid-template-columns: repeat(2, 1fr); } }
    .cli-stat {
        background: #fff; border-radius: 14px; padding: 1rem 1.2rem;
        box-shadow: 0 2px 12px rgba(0,0,0,.05); display: flex; align-items: center; gap: .8rem;
    }
    .cli-stat .cs-icon {
        width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem; color: #fff;
    }
    .cli-stat .cs-value { font-size: 1.3rem; font-weight: 800; color: #1a1a2e; line-height: 1.1; }
    .cli-stat .cs-label { font-size: .72rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
    body.dark-mode .cli-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    body.dark-mode .cli-stat .cs-value { color: #f0f0f7; }
    body.dark-mode .cli-stat .cs-label { color: #9a9ac0; }

    .cli-type-icon {
        width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; color: #fff;
        display: flex; align-items: center; justify-content: center; font-size: .95rem;
    }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-users mr-2 text-success"></i>Clientes y Puntos de Distribución</h2>
        <p>Bodegas, supermercados, colegios y restaurantes a los que distribuyes</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Cliente
        </a>
    </div>
</div>

<div class="cli-stat-row">
    <div class="cli-stat">
        <div class="cs-icon" style="background:linear-gradient(135deg,#1a1a2e,#b5451b);"><i class="fas fa-users"></i></div>
        <div><div class="cs-value">{{ $stats['total'] }}</div><div class="cs-label">Clientes totales</div></div>
    </div>
    <div class="cli-stat">
        <div class="cs-icon" style="background:#2ecc71;"><i class="fas fa-user-check"></i></div>
        <div><div class="cs-value">{{ $stats['activos'] }}</div><div class="cs-label">Activos</div></div>
    </div>
    <div class="cli-stat">
        <div class="cs-icon" style="background:#3498db;"><i class="fas fa-store"></i></div>
        <div><div class="cs-value">{{ $stats['mayoristas'] }}</div><div class="cs-label">Puntos de venta</div></div>
    </div>
    <div class="cli-stat">
        <div class="cs-icon" style="background:#8e44ad;"><i class="fas fa-map-marker-alt"></i></div>
        <div><div class="cs-value">{{ $stats['distritos'] }}</div><div class="cs-label">Distritos cubiertos</div></div>
    </div>
</div>

<form method="GET" action="{{ route('clientes.index') }}" class="filter-bar">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o distrito..." value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Tipo</label>
    <select name="tipo" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach(\App\Models\Cliente::TIPOS as $key => $t)
            <option value="{{ $key }}" {{ request('tipo') === $key ? 'selected' : '' }}>{{ $t['label'] }}</option>
        @endforeach
    </select>
    <label class="fb-label mb-0">Estado</label>
    <select name="estado" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="activo"   {{ request('estado') === 'activo'   ? 'selected' : '' }}>Activo</option>
        <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('tipo') || request('estado'))
        <a href="{{ route('clientes.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($clientes->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>Distrito</th>
                <th>Teléfono</th>
                <th class="text-center">Ventas</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientes as $cliente)
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="cli-type-icon" style="background:{{ $cliente->color_tipo }};"><i class="fas {{ $cliente->icono }}"></i></div>
                        <div class="ml-2">
                            <div class="row-title">{{ $cliente->nombre }}</div>
                            @if($cliente->email)<div class="row-subtitle">{{ $cliente->email }}</div>@endif
                        </div>
                    </div>
                </td>
                <td><span class="badge-soft badge-soft-info">{{ $cliente->tipo_label }}</span></td>
                <td>{{ $cliente->distrito ?? '—' }}</td>
                <td>{{ $cliente->telefono ?? '—' }}</td>
                <td class="text-center"><span class="badge-soft badge-soft-info">{{ $cliente->ventas_count }}</span></td>
                <td class="text-center">
                    <span class="badge-soft {{ ($cliente->estado ?? 'activo') === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ucfirst($cliente->estado ?? 'activo') }}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-icon btn-info js-ver-detalle" data-titulo-detalle="{{ $cliente->nombre }}" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($cliente->nombre !== 'Cliente General')
                        <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Desactivar cliente?"
                            data-confirm="&quot;{{ $cliente->nombre }}&quot; ya no aparecerá disponible para nuevas ventas.">
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
    <div class="card-footer bg-white">{{ $clientes->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-users"></i>
    @if(request('buscar') || request('tipo') || request('estado'))
        <p>No hay clientes que coincidan con esos filtros</p>
        <a href="{{ route('clientes.index') }}" class="btn btn-light"><i class="fas fa-times mr-1"></i>Limpiar filtros</a>
    @else
        <p>Todavía no tienes clientes registrados</p>
        <a href="{{ route('clientes.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
    @endif
</div>
@endif

@endsection
