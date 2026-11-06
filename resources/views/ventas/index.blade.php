@extends('layouts.app')
@section('title', 'Ventas')
@section('breadcrumb') <li class="breadcrumb-item active">Ventas</li> @endsection

@include('partials.reportes-estilos')

@push('styles')
<style>
    .vt-medios { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .vt-medio { display: flex; align-items: center; gap: .55rem; background: #fff; border-radius: 12px; padding: .55rem .85rem; box-shadow: 0 1px 6px rgba(0,0,0,.05); text-decoration: none !important; color: inherit; border: 1.5px solid transparent; }
    .vt-medio:hover { border-color: #eee5dd; color: inherit; }
    .vt-medio.activo { border-color: #b5451b; }
    .vt-medio .ic { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .8rem; }
    .vt-medio b { display: block; font-size: .95rem; color: #1a1a2e; line-height: 1.1; }
    .vt-medio small { color: #8a8a9d; font-size: .72rem; }
    body.dark-mode .vt-medio { background: #1f1f33; }
    body.dark-mode .vt-medio b { color: #f0f0f7; }

    .vt-dia { display: flex; justify-content: space-between; align-items: center; margin: 1.1rem 0 .45rem; font-size: .76rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #8a8a9d; }
    .vt-dia b { color: #1a1a2e; font-size: .85rem; letter-spacing: 0; }
    body.dark-mode .vt-dia b { color: #f0f0f7; }
    .vt-fila { display: grid; grid-template-columns: 56px minmax(160px, 1.1fr) minmax(170px, 1.5fr) 120px 100px 110px auto; gap: .8rem; align-items: center;
        background: #fff; border-radius: 12px; padding: .65rem 1rem; box-shadow: 0 1px 6px rgba(0,0,0,.05); cursor: pointer; }
    .vt-fila + .vt-fila { margin-top: .45rem; }
    .vt-fila:hover { box-shadow: 0 4px 16px rgba(0,0,0,.09); }
    .vt-fila.anulada { opacity: .6; }
    .vt-fila.anulada .vt-total { text-decoration: line-through; }
    body.dark-mode .vt-fila { background: #1f1f33; }
    .vt-hora { font-weight: 800; font-size: .95rem; color: #1a1a2e; }
    body.dark-mode .vt-hora { color: #f0f0f7; }
    .vt-num { font-family: ui-monospace, monospace; font-size: .74rem; color: #8a8a9d; }
    .vt-cli b { display: block; font-size: .9rem; color: #1a1a2e; }
    body.dark-mode .vt-cli b { color: #f0f0f7; }
    .vt-cli small, .vt-prod { font-size: .78rem; color: #8a8a9d; }
    .vt-prod { overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .vt-pago { display: inline-flex; align-items: center; gap: .3rem; font-size: .74rem; font-weight: 700; border-radius: 20px; padding: .18rem .6rem; background: #f3f0ec; color: #555; white-space: nowrap; }
    .vt-total { text-align: right; font-weight: 800; font-size: 1rem; color: #1a1a2e; white-space: nowrap; }
    body.dark-mode .vt-total { color: #f0f0f7; }
    .vt-acc { display: flex; gap: .3rem; justify-content: flex-end; }
    @media (max-width: 991px) {
        .vt-fila { grid-template-columns: 52px 1fr auto; }
        .vt-fila .vt-prod, .vt-fila .vt-vend { display: none; }
        .vt-fila .vt-acc { display: none; }
        .vt-fila .vt-pagocol { grid-column: 2; }
        .vt-fila.completada .vt-est { display: none; }
        .vt-fila.anulada .vt-est { grid-column: 3; text-align: right; }
    }
    .pagination, .pg-list { flex-wrap: wrap; }
</style>
@endpush

@section('content')
@php
    $M = \App\Models\Venta::MEDIOS;
    $filtros = request()->except(['page', 'periodo']);
    $hayFiltros = request()->hasAny(['buscar', 'tipo_pago', 'estado', 'vendedor', 'desde', 'hasta']) || ($periodo && $periodo !== 'todo');
    $vsAyer = $caja['ayer'] > 0 ? round(($caja['total'] - $caja['ayer']) / $caja['ayer'] * 100) : null;
    $porDia = $ventas->getCollection()->groupBy(fn ($v) => $v->fecha_venta->toDateString());
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-cash-register mr-2 text-secondary"></i>Ventas</h2>
        <p>Ventas registradas desde la venta rápida de Productos</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('ventas.exportar-excel', request()->only(['periodo', 'desde', 'hasta'])) }}" class="btn btn-light"><i class="fas fa-file-excel mr-1 text-success"></i>Excel</a>
        @if(auth()->user()->hasPermiso('reportes.ventas'))
            <a href="{{ route('reportes.ventas') }}" class="btn btn-outline-secondary"><i class="fas fa-chart-line mr-1"></i>Reporte</a>
        @endif
    </div>
</div>

{{-- Caja de hoy --}}
<div class="inv-hero" style="--acento:#1e8e5a">
    <div class="ih-icono"><i class="fas fa-cash-register"></i></div>
    <div>
        <small>Caja de hoy · {{ ucfirst(today()->locale('es')->translatedFormat('l d \d\e F')) }}</small>
        <h3>S/ {{ number_format($caja['total'], 2) }}</h3>
        @if($vsAyer !== null)
            <small style="text-transform:none;letter-spacing:0;opacity:.85"><i class="fas fa-arrow-{{ $vsAyer >= 0 ? 'up' : 'down' }} mr-1"></i>{{ abs($vsAyer) }}% {{ $vsAyer >= 0 ? 'más' : 'menos' }} que ayer (S/ {{ number_format($caja['ayer'], 2) }} en todo el día)</small>
        @endif
    </div>
    <div class="ih-datos">
        <div><b>{{ $caja['n'] }}</b><span>ventas{{ $caja['anuladas'] ? ' · ' . $caja['anuladas'] . ' anulada(s)' : '' }}</span></div>
        <div><b>S/ {{ number_format($caja['ticket'], 2) }}</b><span>por venta en promedio</span></div>
        <div><b>{{ $topHoy->first()?->producto?->nombre ?? '—' }}</b><span>lo más vendido{{ $topHoy->first() ? ' (' . (int) $topHoy->first()->cant . ' und)' : '' }}</span></div>
    </div>
</div>

<div class="vt-medios">
    @foreach($M as $clave => $m)
        @php $d = $caja['medios'][$clave] ?? null; @endphp
        @if($d || in_array($clave, ['efectivo', 'yape'], true))
            <a href="{{ route('ventas.index', ['periodo' => 'hoy', 'tipo_pago' => $clave]) }}" class="vt-medio {{ request('tipo_pago') === $clave && $periodo === 'hoy' ? 'activo' : '' }}" title="Ventas de hoy con {{ $m['texto'] }}">
                <span class="ic" style="background: {{ $m['color'] }}"><i class="fas {{ $m['icono'] }}"></i></span>
                <span><b>S/ {{ number_format($d->total ?? 0, 2) }}</b><small>{{ $m['texto'] }} · {{ $d->n ?? 0 }} venta{{ ($d->n ?? 0) == 1 ? '' : 's' }}</small></span>
            </a>
        @endif
    @endforeach
</div>

<div class="inv-chips">
    @foreach(\App\Http\Controllers\VentasController::PERIODOS as $clave => $texto)
        <a href="{{ route('ventas.index', $filtros + ['periodo' => $clave]) }}" class="inv-chip {{ $periodo === (string) $clave ? 'activo' : '' }}">{{ $texto }}</a>
    @endforeach
</div>

<form method="GET" action="{{ route('ventas.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <input type="hidden" name="periodo" value="{{ $periodo }}">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" class="form-control" placeholder="N° de venta, cliente o producto…" value="{{ request('buscar') }}" data-sugerencias="clientes" autocomplete="off">
    </div>
    <label class="fb-label mb-0">Pago</label>
    <select name="tipo_pago" class="form-control" style="width:auto" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach($M as $clave => $m)<option value="{{ $clave }}" {{ request('tipo_pago') === $clave ? 'selected' : '' }}>{{ $m['texto'] }}</option>@endforeach
    </select>
    <label class="fb-label mb-0">Estado</label>
    <select name="estado" class="form-control" style="width:auto" onchange="this.form.submit()">
        <option value="">Todas</option>
        <option value="completada" {{ request('estado') === 'completada' ? 'selected' : '' }}>Completadas</option>
        <option value="anulada" {{ request('estado') === 'anulada' ? 'selected' : '' }}>Anuladas</option>
    </select>
    @if($vendedores->count() > 1)
        <label class="fb-label mb-0">Vendedor</label>
        <select name="vendedor" class="form-control" style="width:auto" onchange="this.form.submit()">
            <option value="">Todos</option>
            @foreach($vendedores as $u)<option value="{{ $u->id }}" {{ request('vendedor') == $u->id ? 'selected' : '' }}>{{ $u->nombre }}</option>@endforeach
        </select>
    @endif
    <details class="d-inline-block" {{ !$periodo ? 'open' : '' }}>
        <summary class="btn btn-light btn-sm mb-0">Fechas exactas</summary>
        <span class="d-inline-flex align-items-center" style="gap:.35rem">
            <input type="date" name="desde" class="form-control" value="{{ request('desde') }}" onchange="this.form.periodo.value=''" aria-label="Desde">
            <input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}" onchange="this.form.periodo.value=''" aria-label="Hasta">
        </span>
    </details>
    <button class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if($hayFiltros)<a href="{{ route('ventas.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>@endif
</form>

<div class="d-flex justify-content-between align-items-center" style="font-size:.82rem;color:#8a8a9d">
    <span>{{ $filtrado['n'] }} venta{{ $filtrado['n'] == 1 ? '' : 's' }}{{ $filtrado['anuladas'] ? ' · ' . $filtrado['anuladas'] . ' anulada(s)' : '' }}{{ $hayFiltros ? ' con estos filtros' : '' }}</span>
    <span>Total: <b class="text-body">S/ {{ number_format($filtrado['total'], 2) }}</b> <small>(IGV incluido)</small></span>
</div>

@forelse($porDia as $fecha => $delDia)
    @php $f = \Carbon\Carbon::parse($fecha); @endphp
    <div class="vt-dia">
        <span><i class="far fa-calendar-alt mr-1"></i>{{ $f->isToday() ? 'Hoy · ' : ($f->isYesterday() ? 'Ayer · ' : '') }}{{ $f->locale('es')->translatedFormat('l d \d\e F') }}</span>
        <span>{{ $delDia->where('estado', 'completada')->count() }} venta(s) · <b>S/ {{ number_format($delDia->where('estado', 'completada')->sum('total'), 2) }}</b></span>
    </div>
    @foreach($delDia as $v)
        @php
            $m = $v->medio;
            $prods = $v->detalles->map(fn ($d) => $d->cantidad . ' ' . ($d->producto->nombre ?? '?'));
        @endphp
        <div class="vt-fila clic-fila {{ $v->estado }}" data-href="{{ route('ventas.show', $v) }}" role="link" tabindex="0" aria-label="Ver venta {{ $v->numero_venta }}">
            <div><div class="vt-hora">{{ $v->fecha_venta->format('H:i') }}</div></div>
            <div class="vt-cli" style="min-width:0">
                <b>{{ $v->cliente->nombre ?? 'Cliente General' }}</b>
                <small><span class="vt-num">{{ $v->numero_venta }}</span><span class="vt-vend"> · {{ $v->usuario->nombre ?? '—' }}</span></small>
            </div>
            <div class="vt-prod">{{ $prods->take(3)->join(', ') }}{{ $prods->count() > 3 ? ' y ' . ($prods->count() - 3) . ' más' : '' }}</div>
            <div class="vt-pagocol"><span class="vt-pago"><i class="fas {{ $m['icono'] }}" style="color: {{ $m['color'] }}"></i>{{ $m['texto'] }}</span></div>
            <div class="vt-total">S/ {{ number_format($v->total, 2) }}</div>
            <div class="vt-est">@if($v->estado === 'anulada')<span class="cp-estado anulada">Anulada</span>@else<span class="cp-estado recibida">Completada</span>@endif</div>
            <div class="vt-acc">
                <a href="{{ route('ventas.ticket', $v) }}" target="_blank" class="btn btn-sm btn-light" title="Imprimir ticket" aria-label="Imprimir ticket de {{ $v->numero_venta }}"><i class="fas fa-receipt"></i></a>
            </div>
        </div>
    @endforeach
@empty
    <div class="empty-state">
        <i class="fas fa-cash-register"></i>
        <p>{{ $hayFiltros ? 'No hay ventas con estos filtros.' : 'Todavía no hay ventas. Se registran desde Productos, con la venta rápida.' }}</p>
    </div>
@endforelse
<div class="mt-3">{{ $ventas->links() }}</div>
@endsection

