@extends('layouts.app')
@section('title', 'Proveedores')
@section('breadcrumb')
    <li class="breadcrumb-item active">Proveedores</li>
@endsection

@include('partials.compras-estilos')

@push('styles')
<style>
    .pv-card .item-card-body { padding-top: 1rem; }
    .pv-cab { display: flex; gap: .7rem; align-items: center; margin-bottom: .6rem; }
    .pv-cab a.pv-nombre { font-weight: 800; color: #1a1a2e; font-size: .98rem; line-height: 1.2; display: block; }
    .pv-cab a.pv-nombre:hover { color: #b5451b; text-decoration: none; }
    body.dark-mode .pv-cab a.pv-nombre { color: #f0f0f7; }
    .pv-cab small { color: #8a8a9d; font-size: .74rem; }
    .pv-numeros { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; margin: .75rem 0 .2rem; text-align: center; }
    .pv-numeros div { background: #faf7f4; border-radius: 10px; padding: .45rem .2rem; }
    .pv-numeros b { display: block; font-size: .92rem; color: #1a1a2e; }
    .pv-numeros span { font-size: .64rem; color: #8a8a9d; text-transform: uppercase; letter-spacing: .03em; font-weight: 700; }
    body.dark-mode .pv-numeros div { background: #24243b; }
    body.dark-mode .pv-numeros b { color: #f0f0f7; }
    .pv-aviso { font-size: .74rem; font-weight: 700; border-radius: 8px; padding: .25rem .55rem; margin-top: .45rem; display: inline-flex; gap: .3rem; align-items: center; }
    .pv-aviso.espera { background: rgba(243,156,18,.15); color: #a5680d; }
    .pv-aviso.orden { background: rgba(52,152,219,.12); color: #1c5d80; }
    .pv-aviso.vacio { background: #eef0f3; color: #6c757d; }
</style>
@endpush

@section('content')
<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-truck mr-2 text-secondary"></i>Proveedores</h2>
        <p>Empresas que abastecen de insumos a la panadería</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('proveedores.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Nuevo proveedor</a>
    </div>
</div>

<div class="inv-resumen">
    <a href="{{ route('proveedores.index') }}" class="ir {{ !request('estado') ? 'activo' : '' }}"><b>{{ $stats['total'] }}</b> proveedores</a>
    <a href="{{ route('proveedores.index', ['estado' => 'activo']) }}" class="ir {{ request('estado') === 'activo' ? 'activo' : '' }}"><i class="fas fa-check-circle" style="color:#1e8e5a"></i><b>{{ $stats['activos'] }}</b> activos</a>
    @if($stats['inactivos'])
        <a href="{{ route('proveedores.index', ['estado' => 'inactivo']) }}" class="ir {{ request('estado') === 'inactivo' ? 'activo' : '' }}"><i class="fas fa-ban" style="color:#6c757d"></i><b>{{ $stats['inactivos'] }}</b> inactivos</a>
    @endif
    <span class="ir"><i class="fas fa-coins" style="color:#d4880f"></i>Comprado este mes: <b>S/ {{ number_format($stats['gasto_mes'], 2) }}</b></span>
    @if($stats['insumos_sin_proveedor'] && auth()->user()->hasPermiso('inventario.materia_prima'))
        <a href="{{ route('materia-prima.index', ['proveedor' => 'ninguno']) }}" class="ir" title="Sin proveedor no se puede convertir su orden automática en compra">
            <i class="fas fa-exclamation-triangle" style="color:#e67e22"></i><b>{{ $stats['insumos_sin_proveedor'] }}</b> insumo{{ $stats['insumos_sin_proveedor'] == 1 ? '' : 's' }} sin proveedor
        </a>
    @endif
</div>

<form method="GET" action="{{ route('proveedores.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" data-sugerencias="proveedores" autocomplete="off" class="form-control" placeholder="Buscar por nombre, RUC, contacto o teléfono..." value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Estado</label>
    <select name="estado" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="activo"   {{ request('estado') === 'activo'   ? 'selected' : '' }}>Activo</option>
        <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
    </select>
    <label class="fb-label mb-0">Ordenar</label>
    <select name="orden" class="form-control" style="width:auto;" onchange="this.form.submit()">
        @foreach(\App\Http\Controllers\ProveedoresController::ORDENES as $clave => $texto)
            <option value="{{ $clave === 'nombre' ? '' : $clave }}" {{ (request('orden') ?: 'nombre') === $clave ? 'selected' : '' }}>{{ $texto }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('estado') || request('orden'))
        <a href="{{ route('proveedores.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($proveedores->count() > 0)
<div class="card-grid">
    @foreach($proveedores as $p)
        @php
            $usosProveedor = [];
            if ($p->compras_count > 0)       $usosProveedor[] = $p->compras_count . ' compra(s)';
            if ($p->ordenes_pendientes > 0)  $usosProveedor[] = 'órdenes automáticas';
            if ($p->insumos_count > 0)       $usosProveedor[] = 'es proveedor de ' . $p->insumos_count . ' insumo(s)';
            $tieneHistorial = count($usosProveedor) > 0 || \App\Models\OrdenAutomatica::where('id_proveedor', $p->id)->exists();
            $estaActivo = $p->estado === 'activo';
        @endphp
        <div class="item-card pv-card clic-tarjeta {{ !$estaActivo ? 'is-inactive' : '' }}" data-href="{{ route('proveedores.show', $p) }}" role="link" tabindex="0" aria-label="Ver proveedor {{ $p->nombre }}">
            <div class="item-card-body">
                <div class="pv-cab">
                    <div class="pv-avatar {{ $estaActivo ? '' : 'inactivo' }}">{{ $p->getIniciales() }}</div>
                    <div style="min-width:0">
                        <a href="{{ route('proveedores.show', $p) }}" class="pv-nombre">{{ $p->nombre }}</a>
                        <small>{{ $p->ruc ? 'RUC ' . $p->ruc : 'Sin RUC' }}{{ $p->contacto ? ' · ' . $p->contacto : '' }}</small>
                    </div>
                </div>

                <div class="pv-contacto">
                    @if($p->telefono)<a href="tel:{{ preg_replace('/[^\d+]/', '', $p->telefono) }}" title="Llamar"><i class="fas fa-phone"></i>{{ $p->telefono }}</a>@endif
                    @if($p->whatsapp)<a class="wa" href="https://wa.me/{{ $p->whatsapp }}" target="_blank" rel="noopener" title="Escribir por WhatsApp"><i class="fab fa-whatsapp"></i>WhatsApp</a>@endif
                    @if($p->email)<a href="mailto:{{ $p->email }}" title="{{ $p->email }}"><i class="fas fa-envelope"></i>Correo</a>@endif
                    @if(!$p->telefono && !$p->email)<span class="small text-muted">Sin datos de contacto</span>@endif
                </div>

                <div class="pv-numeros">
                    <div><b>{{ $p->insumos_count }}</b><span>Insumos</span></div>
                    <div><b>{{ $p->compras_count }}</b><span>Compras</span></div>
                    <div><b title="Total comprado (sin anuladas)">S/ {{ number_format($p->total_comprado ?? 0, 0) }}</b><span>Comprado</span></div>
                </div>
                <div class="small text-muted mt-2">
                    <i class="far fa-calendar mr-1"></i>{{ $p->ultima_compra ? 'Última compra: ' . \Carbon\Carbon::parse($p->ultima_compra)->locale('es')->diffForHumans() : 'Todavía no le compramos' }}
                </div>
                @if($p->pendientes_count)
                    <span class="pv-aviso espera"><i class="fas fa-truck"></i>{{ $p->pendientes_count }} pedido{{ $p->pendientes_count == 1 ? '' : 's' }} por recibir</span>
                @endif
                @if($p->ordenes_pendientes)
                    <span class="pv-aviso orden"><i class="fas fa-robot"></i>{{ $p->ordenes_pendientes }} orden{{ $p->ordenes_pendientes == 1 ? '' : 'es' }} automática{{ $p->ordenes_pendientes == 1 ? '' : 's' }}</span>
                @endif
                @if($estaActivo && !$p->insumos_count)
                    <span class="pv-aviso vacio"><i class="fas fa-info-circle"></i>Sin insumos asignados</span>
                @endif
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
                    @if($estaActivo && auth()->user()->hasPermiso('compras.compras'))
                        <a href="{{ route('compras.create', ['proveedor' => $p->id]) }}" class="btn btn-icon btn-primary" title="Nueva compra a este proveedor"><i class="fas fa-cart-plus"></i></a>
                    @endif
                    <a href="{{ route('proveedores.edit', ['proveedor' => $p, 'volver' => request()->fullUrl()]) }}" class="btn btn-icon btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                    @if($estaActivo)
                        <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                            data-blocked-title="Desactívalo primero"
                            data-blocked-message="Por seguridad, un proveedor activo no se puede eliminar directamente. Usa el interruptor para desactivar a &quot;{{ $p->nombre }}&quot; antes de eliminarlo.">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @elseif($tieneHistorial)
                        <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                            data-blocked-title="No se puede eliminar este proveedor"
                            data-blocked-message="&quot;{{ $p->nombre }}&quot; {{ $usosProveedor ? 'tiene ' . implode(' y ', $usosProveedor) : 'tiene historial' }}. Permanecerá desactivado para conservar el historial.">
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
