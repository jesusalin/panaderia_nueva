@extends('layouts.app')
@section('title', 'Usuarios')
@section('breadcrumb') <li class="breadcrumb-item active">Usuarios</li> @endsection

@push('styles')
<style>
    .usr-stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .usr-stat {
        background: #fff; border-radius: 12px; padding: 1rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .8rem;
    }
    body.dark-mode .usr-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .usr-stat .us-icon {
        width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .usr-stat .us-value { font-size: 1.35rem; font-weight: 800; color: #1a1a2e; line-height: 1; }
    body.dark-mode .usr-stat .us-value { color: #f0f0f7; }
    .usr-stat .us-label { font-size: .74rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .2rem; }
    .usr-stat.total     { border-left-color: #3498db; }
    .usr-stat.total .us-icon     { background: rgba(52,152,219,.14); color: #2170a3; }
    .usr-stat.activos   { border-left-color: #2ecc71; }
    .usr-stat.activos .us-icon   { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .usr-stat.inactivos { border-left-color: #adb5bd; }
    .usr-stat.inactivos .us-icon { background: rgba(173,181,189,.18); color: #6c757d; }
    .usr-stat.admins    { border-left-color: #e74c3c; }
    .usr-stat.admins .us-icon    { background: rgba(231,76,60,.12); color: #c0392b; }
    .usr-stat.conectados { border-left-color: #2ecc71; }
    .usr-stat.conectados .us-icon { background: rgba(46,204,113,.14); color: #1e8e5a; }
    @media (max-width: 1100px) { .usr-stats { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 700px)  { .usr-stats { grid-template-columns: repeat(2, 1fr); } }

    .usr-card-body { padding: 1.1rem 1.1rem .2rem; flex: 1; }
    .usr-avatar-row { display: flex; align-items: center; gap: .7rem; margin-bottom: .5rem; }
    .usr-avatar { position: relative;
        width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.05rem; color: #fff; background: linear-gradient(135deg,#4a4a5e,#1a1a2e); flex-shrink: 0;
    }
    .usr-conexion-dot {
        position: absolute; bottom: -1px; right: -1px; width: 13px; height: 13px; border-radius: 50%;
        background: #adb5bd; border: 2px solid #fff;
    }
    .usr-conexion-dot.online { background: #2ecc71; box-shadow: 0 0 0 2px rgba(46,204,113,.25); }
    body.dark-mode .usr-conexion-dot { border-color: #1f1f33; }
    .usr-name-wrap { min-width: 0; }
    .usr-name { font-weight: 800; color: #1a1a2e; font-size: .98rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    body.dark-mode .usr-name { color: #f0f0f7; }
    .usr-conexion-texto { font-size: .74rem; font-weight: 700; }
    .usr-conexion-texto.online { color: #1e8e5a; }
    .usr-conexion-texto.offline { color: #8a8a9d; }
    body.dark-mode .usr-conexion-texto.online { color: #6ee7a5; }
    .usr-card-row { display: flex; align-items: center; gap: .5rem; font-size: .83rem; color: #6c757d; padding: .28rem 0; }
    .usr-card-row i { width: 16px; color: #adb5bd; }
    body.dark-mode .usr-card-row { color: #b0b0cc; }
    .usr-badges { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .6rem; }
    .usr-ventas-row {
        display: flex; align-items: center; justify-content: space-between; margin-top: .7rem;
        padding: .55rem .7rem; background: rgba(30,142,90,.06); border-radius: 8px; font-size: .8rem;
    }
    body.dark-mode .usr-ventas-row { background: rgba(30,142,90,.12); }
    .usr-ventas-row strong { color: #1e8e5a; }
    body.dark-mode .usr-ventas-row strong { color: #6ee7a5; }
</style>
@endpush

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

<div class="usr-stats">
    <div class="usr-stat total">
        <div class="us-icon"><i class="fas fa-users"></i></div>
        <div><div class="us-value">{{ $stats['total'] }}</div><div class="us-label">Usuarios</div></div>
    </div>
    <div class="usr-stat activos">
        <div class="us-icon"><i class="fas fa-check-circle"></i></div>
        <div><div class="us-value">{{ $stats['activos'] }}</div><div class="us-label">Activos</div></div>
    </div>
    <div class="usr-stat inactivos">
        <div class="us-icon"><i class="fas fa-ban"></i></div>
        <div><div class="us-value">{{ $stats['inactivos'] }}</div><div class="us-label">Inactivos</div></div>
    </div>
    <div class="usr-stat conectados">
        <div class="us-icon"><i class="fas fa-circle"></i></div>
        <div><div class="us-value">{{ $stats['conectados'] }}</div><div class="us-label">Conectados ahora</div></div>
    </div>
    <div class="usr-stat admins">
        <div class="us-icon"><i class="fas fa-user-shield"></i></div>
        <div><div class="us-value">{{ $stats['admins'] }}</div><div class="us-label">Administradores</div></div>
    </div>
</div>

<form method="GET" action="{{ route('usuarios.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre, usuario o email..." value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Rol</label>
    <select name="rol" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach($roles as $r)
            <option value="{{ $r->id }}" {{ request('rol') == $r->id ? 'selected' : '' }}>{{ ucfirst($r->nombre) }}</option>
        @endforeach
    </select>
    <label class="fb-label mb-0">Estado</label>
    <select name="estado" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="activo"   {{ request('estado') === 'activo'   ? 'selected' : '' }}>Activo</option>
        <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('rol') || request('estado'))
        <a href="{{ route('usuarios.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($usuarios->count() > 0)
<div class="card-grid">
    @foreach($usuarios as $u)
        @php $esAdmin = $u->rol->nombre === 'admin'; @endphp
        <div class="item-card {{ $u->estado !== 'activo' ? 'is-inactive' : '' }}">
            <div class="item-card-body usr-card-body">
                <div class="usr-avatar-row">
                    <div class="usr-avatar">
                        {{ strtoupper(substr($u->nombre,0,1)) }}
                        <span class="usr-conexion-dot {{ $u->estaConectado() ? 'online' : '' }}"
                            title="{{ $u->estaConectado() ? 'En línea' : 'Desconectado' }}"></span>
                    </div>
                    <div class="usr-name-wrap">
                        <div class="usr-name">{{ $u->nombre }}</div>
                        @if($u->estaConectado())
                            <span class="usr-conexion-texto online"><i class="fas fa-circle mr-1" style="font-size:.55rem;"></i>En línea</span>
                        @else
                            <span class="usr-conexion-texto offline">{{ $u->ultimo_acceso ? 'Últ. vez '.$u->ultimo_acceso->diffForHumans() : 'Nunca ha ingresado' }}</span>
                        @endif
                        @if($u->apodo)<br><span class="badge-soft badge-soft-secondary">{{ $u->apodo }}</span>@endif
                    </div>
                </div>

                <div class="item-card-cat"><code>{{ '@'.$u->usuario }}</code></div>

                <div class="usr-card-row"><i class="fas fa-envelope"></i>{{ $u->email }}</div>

                <div class="usr-badges">
                    <span class="badge-soft {{ $esAdmin ? 'badge-soft-danger' : 'badge-soft-secondary' }}">{{ ucfirst($u->rol->nombre) }}</span>
                    <span class="badge-soft {{ $u->estado === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ ucfirst($u->estado) }}</span>
                </div>

                <div class="mt-2">
                    @if($esAdmin)
                        <span class="usr-card-row" style="padding:0;"><i class="fas fa-infinity"></i>Acceso total al sistema</span>
                    @elseif($u->permisos->isEmpty())
                        <span class="usr-card-row" style="padding:0;color:#ced4da;font-style:italic;">Sin módulos asignados</span>
                    @else
                        <div class="usr-badges">
                            @foreach($u->permisos as $p)
                                <span class="badge-soft badge-soft-info">{{ \App\Models\Usuario::MODULOS[$p->modulo] ?? $p->modulo }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($u->ventas_count > 0)
                    <div class="usr-ventas-row">
                        <span><i class="fas fa-cash-register mr-1 text-muted"></i><strong>{{ $u->ventas_count }}</strong> venta(s) registrada(s)</span>
                        <span class="text-muted">Última: {{ \Illuminate\Support\Carbon::parse($u->ventas_max_fecha_venta)->format('d/m/Y') }}</span>
                    </div>
                @elseif($u->hasModulo('ventas') && !$esAdmin)
                    <div class="usr-ventas-row" style="background:rgba(231,76,60,.07);">
                        <span class="text-muted"><i class="fas fa-cash-register mr-1"></i>Todavía no ha atendido ninguna venta</span>
                    </div>
                @endif
            </div>

            <div class="item-card-footer">
                <form action="{{ route('usuarios.toggle-estado', $u) }}" method="POST">
                    @csrf @method('PUT')
                    @if(auth()->id() === $u->id)
                        <button type="submit" class="estado-switch {{ $u->estado === 'activo' ? 'activa' : '' }}" disabled title="No puedes desactivar tu propia cuenta">
                            <span class="track"></span>
                            <span class="txt">{{ $u->estado === 'activo' ? 'Activo' : 'Inactivo' }}</span>
                        </button>
                    @else
                        <button type="submit" class="estado-switch {{ $u->estado === 'activo' ? 'activa' : '' }}">
                            <span class="track"></span>
                            <span class="txt">{{ $u->estado === 'activo' ? 'Activo' : 'Inactivo' }}</span>
                        </button>
                    @endif
                </form>
                <div class="btn-icon-group">
                    <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-icon btn-warning" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    @if(auth()->id() === $u->id)
                        <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                            data-blocked-title="No puedes eliminar tu propia cuenta"
                            data-blocked-message="Por seguridad, no puedes eliminar la cuenta con la que iniciaste sesión.">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @elseif($u->estado === 'activo')
                        <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                            data-blocked-title="Desactívalo primero"
                            data-blocked-message="Por seguridad, un usuario activo no se puede eliminar directamente. Usa el interruptor para desactivar a &quot;{{ $u->nombre }}&quot; antes de eliminarlo, así evitamos borrados por error.">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @else
                        @php
                            $usosUsuario = [];
                            if ($u->ventas_count > 0)      $usosUsuario[] = $u->ventas_count . ' venta(s)';
                            if ($u->compras_count > 0)     $usosUsuario[] = $u->compras_count . ' compra(s)';
                            if ($u->producciones_count > 0)$usosUsuario[] = $u->producciones_count . ' producción(es)';
                            if ($u->movimientos_count > 0) $usosUsuario[] = 'movimientos de inventario';
                            if ($u->kardex_count > 0)      $usosUsuario[] = 'movimientos de productos';
                            if ($u->tiempos_count > 0)     $usosUsuario[] = 'registros de tiempos de operación';
                            $tieneHistorialUsuario = count($usosUsuario) > 0;
                        @endphp
                        @if($tieneHistorialUsuario)
                            <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                                data-blocked-title="No se puede eliminar este usuario"
                                data-blocked-message="&quot;{{ $u->nombre }}&quot; tiene {{ implode(', ', $usosUsuario) }}. Permanecerá desactivado para conservar el historial.">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        @else
                            <form action="{{ route('usuarios.destroy', $u) }}" method="POST" class="js-confirm"
                                data-confirm-title="¿Eliminar este usuario?"
                                data-confirm="&quot;{{ $u->nombre }}&quot; se borrará por completo del sistema. Esta acción NO se puede deshacer.">
                                @csrf @method('DELETE')
                                <button class="btn btn-icon btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-3">{{ $usuarios->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-users"></i>
    <p>{{ request('buscar') || request('rol') || request('estado') ? 'Ningún usuario coincide con ese filtro' : 'No hay usuarios registrados' }}</p>
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@endsection
