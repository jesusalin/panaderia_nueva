@extends('layouts.app')
@section('title', 'Clientes')
@section('breadcrumb') <li class="breadcrumb-item active">Clientes</li> @endsection

@include('partials.reportes-estilos')
@include('partials.compras-estilos')

@push('styles')
<style>
    .cl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 1rem; }
    .cl-card { display: flex; flex-direction: column; border-top: 4px solid var(--tc, #b5451b); }
    .cl-card .item-card-body { padding: 1rem 1.1rem .8rem; flex: 1; }
    .cl-cab { display: flex; gap: .75rem; align-items: center; margin-bottom: .55rem; }
    .cl-avatar { width: 46px; height: 46px; border-radius: 12px; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;
        background: linear-gradient(135deg, color-mix(in srgb, var(--tc) 70%, #000), var(--tc)); font-size: 1rem; }
    .cl-card.is-inactive .cl-avatar { background: #adb5bd; }
    .cl-nombre { font-weight: 800; color: #1a1a2e; line-height: 1.2; font-size: .98rem; }
    body.dark-mode .cl-nombre { color: #f0f0f7; }
    .cl-sub { font-size: .76rem; color: #8a8a9d; }
    .cl-tipo { display: inline-flex; align-items: center; gap: .3rem; font-size: .7rem; font-weight: 800; border-radius: 20px; padding: .12rem .55rem;
        background: color-mix(in srgb, var(--tc) 12%, transparent); color: var(--tc); }
    .cl-numeros { display: grid; grid-template-columns: repeat(3, 1fr); gap: .4rem; margin-top: .7rem; }
    .cl-numeros div { background: #faf7f4; border-radius: 9px; padding: .4rem .3rem; text-align: center; }
    .cl-numeros b { display: block; font-size: .92rem; color: #1a1a2e; line-height: 1.15; }
    .cl-numeros span { font-size: .64rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .02em; }
    body.dark-mode .cl-numeros div { background: #24243b; }
    body.dark-mode .cl-numeros b { color: #f0f0f7; }
    .cl-ultima { font-size: .76rem; color: #8a8a9d; margin-top: .55rem; }
    .cl-ultima.alerta { color: #c0392b; font-weight: 700; }
    .cl-ultima.bien { color: #1e8e5a; font-weight: 700; }
    .cl-nota { font-size: .76rem; color: #6c5a48; background: #fff8ec; border-radius: 8px; padding: .3rem .55rem; margin-top: .5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    body.dark-mode .cl-nota { background: rgba(212,136,15,.12); color: #f0d6a8; }
    .cl-general { font-size: .72rem; font-weight: 700; color: #2170a3; background: #eef4fb; border-radius: 8px; padding: .3rem .55rem; margin-top: .5rem; }
    body.dark-mode .cl-general { background: rgba(52,152,219,.15); color: #9fd0f5; }
    .cl-tipos { display: flex; gap: .35rem; flex-wrap: wrap; margin-bottom: .8rem; }
    .cl-tipos a { display: inline-flex; align-items: center; gap: .35rem; border: 1.5px solid #eee5dd; background: #fff; border-radius: 20px; padding: .22rem .7rem;
        font-size: .76rem; font-weight: 700; color: #555; text-decoration: none !important; }
    .cl-tipos a i { color: var(--tc); }
    .cl-tipos a small { color: #adb5bd; font-weight: 700; }
    .cl-tipos a.activo { border-color: var(--tc, #b5451b); background: color-mix(in srgb, var(--tc, #b5451b) 10%, #fff); color: #1a1a2e; }
    body.dark-mode .cl-tipos a { background: #1f1f33; border-color: #33334d; color: #ccc; }
    body.dark-mode .cl-tipos a.activo { background: #2c2c44; color: #fff; }
    .pagination, .pg-list { flex-wrap: wrap; }
</style>
@endpush

@section('content')
@php
    $T = \App\Models\Cliente::TIPOS;
    $filtros = request()->except(['page', 'vista']);
    $hayFiltros = request()->hasAny(['buscar', 'tipo']) || $vista !== 'todos';
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-users mr-2 text-success"></i>Clientes</h2>
        <p>Bodegas, colegios, restaurantes y personas que nos compran</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('clientes.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Nuevo cliente</a>
    </div>
</div>

<div class="inv-hero" style="--acento:#1e8e5a">
    <div class="ih-icono"><i class="fas fa-users"></i></div>
    <div>
        <small>Clientes este mes</small>
        <h3>S/ {{ number_format($stats['total_mes'], 2) }}</h3>
        <small style="text-transform:none;letter-spacing:0;opacity:.85">comprados por {{ $stats['compraron'] }} cliente(s) con nombre · mostrador S/ {{ number_format($stats['mostrador'], 2) }}</small>
    </div>
    <div class="ih-datos">
        <div><b>{{ $stats['activos'] }}</b><span>clientes activos{{ $stats['inactivos'] ? ' · ' . $stats['inactivos'] . ' inactivo(s)' : '' }}</span></div>
        <div><b>{{ $stats['top']?->cliente?->nombre ?? '—' }}</b><span>el que más compró este mes{{ $stats['top'] ? ' (S/ ' . number_format($stats['top']->total, 0) . ')' : '' }}</span></div>
        <div><b>{{ $stats['nuevos'] }}</b><span>nuevo(s) este mes</span></div>
    </div>
</div>

@if($stats['recuperar'])
<div class="inv-resumen">
    <a href="{{ route('clientes.index', ['vista' => 'recuperar']) }}" class="ir {{ $vista === 'recuperar' ? 'activo' : '' }}">
        <i class="fas fa-user-clock" style="color:#c0392b"></i><b>{{ $stats['recuperar'] }}</b> cliente(s) dejaron de comprar hace más de {{ \App\Models\Cliente::DIAS_SIN_COMPRAR }} días · escríbeles
    </a>
</div>
@endif

<div class="inv-chips">
    @foreach(\App\Http\Controllers\ClientesController::VISTAS as $clave => $texto)
        <a href="{{ route('clientes.index', $filtros + ['vista' => $clave]) }}" class="inv-chip {{ $vista === $clave ? 'activo' : '' }}">{{ $texto }}</a>
    @endforeach
</div>

<form method="GET" action="{{ route('clientes.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <input type="hidden" name="vista" value="{{ $vista }}">
    @if(request('tipo'))<input type="hidden" name="tipo" value="{{ request('tipo') }}">@endif
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" data-sugerencias="clientes" autocomplete="off" class="form-control" placeholder="Nombre, RUC, DNI, teléfono, distrito o contacto…" value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Ordenar</label>
    <select name="orden" class="form-control" style="width:auto" onchange="this.form.submit()">
        @foreach(\App\Http\Controllers\ClientesController::ORDENES as $clave => $texto)
            <option value="{{ $clave }}" {{ $orden === $clave ? 'selected' : '' }}>{{ $texto }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-search mr-1"></i>Buscar</button>
    @if($hayFiltros || request('orden'))
        <a href="{{ route('clientes.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

<div class="cl-tipos">
    <a href="{{ route('clientes.index', request()->except(['page', 'tipo'])) }}" class="{{ request('tipo') ? '' : 'activo' }}" style="--tc:#b5451b">Todos los tipos</a>
    @foreach($T as $clave => $t)
        @if($porTipo[$clave] ?? 0)
            <a href="{{ route('clientes.index', array_merge(request()->except('page'), ['tipo' => $clave])) }}" class="{{ request('tipo') === $clave ? 'activo' : '' }}" style="--tc: {{ $t['color'] }}">
                <i class="fas {{ $t['icono'] }}"></i>{{ $t['label'] }} <small>{{ $porTipo[$clave] }}</small>
            </a>
        @endif
    @endforeach
</div>

@if($clientes->count())
<div class="cl-grid">
    @foreach($clientes as $c)
        @php
            $activo = ($c->estado ?? 'activo') !== 'inactivo';
            $general = $c->esGeneral();
            $dias = $c->ultima_compra ? (int) \Carbon\Carbon::parse($c->ultima_compra)->startOfDay()->diffInDays(today()) : null;
            $recuperar = $activo && !$general && $dias !== null && $dias >= \App\Models\Cliente::DIAS_SIN_COMPRAR;
        @endphp
        <div class="item-card cl-card clic-tarjeta {{ $activo ? '' : 'is-inactive' }}" style="--tc: {{ $c->color_tipo }}"
             data-href="{{ route('clientes.show', $c) }}" role="link" tabindex="0" aria-label="Ver cliente {{ $c->nombre }}">
            <div class="item-card-body">
                <div class="cl-cab">
                    <div class="cl-avatar" title="{{ $c->tipo_label }}">@if($general)<i class="fas fa-store-alt"></i>@else{{ $c->getIniciales() }}@endif</div>
                    <div style="min-width:0">
                        <div class="cl-nombre">{{ $c->nombre }}</div>
                        <div class="cl-sub">
                            <span class="cl-tipo"><i class="fas {{ $c->icono }}"></i>{{ $c->tipo_label }}</span>
                            {{ $c->distrito ? ' · ' . $c->distrito : '' }}{{ !$activo ? ' · inactivo' : '' }}
                        </div>
                    </div>
                </div>

                @if($general)
                    <div class="cl-general"><i class="fas fa-info-circle mr-1"></i>Ventas de mostrador sin datos del cliente.</div>
                @else
                    <div class="cl-sub mb-2">{{ $c->documento ?? 'Sin RUC ni DNI' }}{{ $c->contacto ? ' · ' . $c->contacto : '' }}</div>
                    <div class="pv-contacto">
                        @if($c->whatsapp)<a class="wa" href="https://wa.me/{{ $c->whatsapp }}" target="_blank" rel="noopener" title="Escribir por WhatsApp"><i class="fab fa-whatsapp"></i>WhatsApp</a>@endif
                        @if($c->telefono)<a href="tel:{{ preg_replace('/[^\d+]/', '', $c->telefono) }}" title="Llamar"><i class="fas fa-phone"></i>{{ $c->telefono }}</a>@endif
                        @if($c->email)<a href="mailto:{{ $c->email }}" title="{{ $c->email }}"><i class="fas fa-envelope"></i>Correo</a>@endif
                        @if(!$c->telefono && !$c->email)<span class="small text-muted">Sin datos de contacto</span>@endif
                    </div>
                @endif

                <div class="cl-numeros">
                    <div><b>{{ $c->compras_count }}</b><span>Compras</span></div>
                    <div><b>S/ {{ number_format($c->total_comprado ?? 0, 0) }}</b><span>Total</span></div>
                    <div><b>S/ {{ number_format($c->total_mes ?? 0, 0) }}</b><span>Este mes</span></div>
                </div>
                @php $frecuente = !$general && $c->compras_30 >= 4; @endphp
                <div class="cl-ultima {{ $recuperar ? 'alerta' : ($frecuente ? 'bien' : '') }}">
                    <i class="far fa-calendar mr-1"></i>
                    @if(!$c->ultima_compra) Todavía no compra
                    @else Última compra: {{ $dias === 0 ? 'hoy' : ($dias === 1 ? 'ayer' : 'hace ' . $dias . ' días') }}
                        @if($recuperar) · <b>por recuperar</b>@elseif($frecuente) · frecuente ({{ $c->compras_30 }} en 30 días)@endif
                    @endif
                </div>
                @if($c->notas)<div class="cl-nota" title="{{ $c->notas }}"><i class="fas fa-sticky-note mr-1"></i>{{ $c->notas }}</div>@endif
            </div>

            <div class="item-card-footer">
                @if($general)
                    <span class="badge-soft badge-soft-info">Siempre activo</span>
                @else
                    <form action="{{ route('clientes.toggle-estado', $c) }}" method="POST">
                        @csrf @method('PUT')
                        <button type="submit" class="estado-switch {{ $activo ? 'activa' : '' }}" title="{{ $activo ? 'Desactivar' : 'Activar' }}">
                            <span class="track"></span><span class="txt">{{ $activo ? 'Activo' : 'Inactivo' }}</span>
                        </button>
                    </form>
                @endif
                <div class="btn-icon-group">
                    <a href="{{ route('clientes.show', $c) }}" class="btn btn-icon btn-info js-ver-detalle" data-titulo-detalle="{{ $c->nombre }}" title="Vista rápida"><i class="fas fa-eye"></i></a>
                    <a href="{{ route('clientes.edit', ['cliente' => $c, 'volver' => request()->fullUrl()]) }}" class="btn btn-icon btn-warning" title="Editar"><i class="fas fa-edit"></i></a>
                    @if(!$general)
                        @if($c->compras_count || $activo)
                            <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                                data-blocked-title="{{ $c->compras_count ? 'No se puede eliminar' : 'Desactívalo primero' }}"
                                data-blocked-message="{{ $c->compras_count ? '“' . $c->nombre . '” tiene ' . $c->compras_count . ' compra(s). Para conservar su historial, solo se puede desactivar con el interruptor.' : 'Por seguridad, un cliente activo no se elimina directamente. Usa el interruptor para desactivar a “' . $c->nombre . '” y luego elimínalo.' }}">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        @else
                            <form action="{{ route('clientes.destroy', $c) }}" method="POST" class="js-confirm"
                                  data-confirm-title="¿Eliminar cliente?" data-confirm="“{{ $c->nombre }}” no tiene compras. Se elimina para siempre.">
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
<div class="mt-3">{{ $clientes->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-users"></i>
    @if($hayFiltros)
        <p>No hay clientes con estos filtros.</p>
        <a href="{{ route('clientes.index') }}" class="btn btn-light"><i class="fas fa-times mr-1"></i>Limpiar filtros</a>
    @else
        <p>Todavía no tienes clientes registrados.</p>
        <a href="{{ route('clientes.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Registrar el primero</a>
    @endif
</div>
@endif
@endsection
