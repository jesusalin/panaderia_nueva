@extends('layouts.app')
@section('title', 'Compras')
@section('breadcrumb')
    <li class="breadcrumb-item active">Compras</li>
@endsection

@push('scripts')
<script>
// Toda la fila abre la compra (menos los botones que tiene dentro)
document.querySelectorAll('.cp-fila[data-href]').forEach(function (f) {
    f.style.cursor = 'pointer';
    f.addEventListener('click', function (e) { if (!e.target.closest('a, button')) location.href = f.dataset.href; });
    f.addEventListener('keydown', function (e) { if (e.key === 'Enter' && e.target === f) location.href = f.dataset.href; });
});
</script>
@endpush

@include('partials.compras-estilos')

@section('content')
@php
    $C = \App\Support\Cantidad::class;
    $filtros = request()->except(['page', 'periodo']);
    $hayFiltros = request()->hasAny(['buscar', 'id_proveedor', 'estado', 'desde', 'hasta']) || ($periodo && $periodo !== 'todo');
    $variacion = $stats['gasto_mes_ant'] > 0 ? round(($stats['gasto_mes'] - $stats['gasto_mes_ant']) / $stats['gasto_mes_ant'] * 100) : null;
    $porRecibir = !request('estado') && !request('buscar') && $stats['pendientes'] > 0
        ? \App\Models\Compra::with(['proveedor', 'detalles.materia'])->where('estado', 'pendiente')->orderBy('fecha_compra')->limit(6)->get() : collect();
    $nombreEstado = function ($c) { return $c->estado === 'pendiente' && $c->atrasada ? 'atrasada' : $c->estado; };
    $textoEstado = ['pendiente' => 'Por recibir', 'atrasada' => 'Atrasada', 'recibida' => 'Recibida', 'anulada' => 'Anulada'];
    $iconoEstado = ['pendiente' => 'fa-truck', 'atrasada' => 'fa-hourglass-end', 'recibida' => 'fa-check', 'anulada' => 'fa-ban'];
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-shopping-cart mr-2 text-secondary"></i>Compras</h2>
        <p>Pedidos de insumos a los proveedores: se registran, se reciben y recién ahí suben el stock</p>
    </div>
    <div class="toolbar-actions">
        @if(auth()->user()->hasPermiso('compras.ordenes'))
            @php $ordenesPend = \App\Models\OrdenAutomatica::where('estado', 'pendiente')->count(); @endphp
            <a href="{{ route('ordenes-automaticas.index') }}" class="btn btn-outline-secondary"><i class="fas fa-robot mr-1"></i>Órdenes automáticas
                @if($ordenesPend)<span class="badge badge-danger ml-1">{{ $ordenesPend }}</span>@endif
            </a>
        @endif
        <a href="{{ route('compras.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Nueva compra</a>
    </div>
</div>

<div class="inv-resumen">
    <a href="{{ route('compras.index', ['estado' => 'pendiente']) }}" class="ir {{ request('estado') === 'pendiente' ? 'activo' : '' }}">
        <i class="fas fa-truck" style="color:#d4880f"></i><b>{{ $stats['pendientes'] }}</b> por recibir @if($stats['pendientes'])<small>(S/ {{ number_format($stats['monto_pendiente'], 2) }})</small>@endif
    </a>
    @if($stats['atrasadas'])
        <a href="{{ route('compras.index', ['estado' => 'atrasada']) }}" class="ir {{ request('estado') === 'atrasada' ? 'activo' : '' }}" title="Pendientes con más de {{ \App\Models\Compra::DIAS_ATRASO }} días">
            <i class="fas fa-hourglass-end" style="color:#c0392b"></i><b>{{ $stats['atrasadas'] }}</b> atrasada{{ $stats['atrasadas'] == 1 ? '' : 's' }}
        </a>
    @endif
    <a href="{{ route('compras.index', ['periodo' => 'mes']) }}" class="ir {{ $periodo === 'mes' && !request('estado') ? 'activo' : '' }}">
        <i class="fas fa-coins" style="color:#1e8e5a"></i>Este mes: <b>S/ {{ number_format($stats['gasto_mes'], 2) }}</b>
        <small>en {{ $stats['compras_mes'] }} compra{{ $stats['compras_mes'] == 1 ? '' : 's' }}</small>
        @if($variacion !== null)
            <span class="cp-var {{ $variacion > 0 ? 'sube' : ($variacion < 0 ? 'baja' : 'igual') }}" title="Comparado con los mismos días del mes anterior (S/ {{ number_format($stats['gasto_mes_ant'], 2) }})">
                <i class="fas fa-arrow-{{ $variacion >= 0 ? 'up' : 'down' }}"></i> {{ abs($variacion) }}% vs mes anterior
            </span>
        @endif
    </a>
</div>

@if($porRecibir->count())
    <p class="cp-titulo"><i class="fas fa-truck-loading"></i>Por recibir — cuando llegue el pedido, pulsa Recibir para que suba el stock</p>
    <div class="cp-porrecibir">
        @foreach($porRecibir as $c)
            <div class="cp-pr {{ $c->atrasada ? 'atrasada' : '' }}">
                <div class="pr-top">
                    <div>
                        <b>{{ $c->proveedor->nombre ?? '—' }}</b><br>
                        <small>{{ $c->codigo }}{{ $c->numero_doc ? ' · ' . $c->numero_doc : '' }} · {{ $c->detalles->count() }} insumo{{ $c->detalles->count() == 1 ? '' : 's' }}</small>
                    </div>
                    <span class="pr-total">S/ {{ number_format($c->total, 2) }}</span>
                </div>
                <small>{{ \Illuminate\Support\Str::limit($c->detalles->map(fn ($d) => $d->materia->nombre ?? '')->join(', '), 70) }}</small>
                <small class="{{ $c->atrasada ? 'text-danger font-weight-bold' : '' }}">
                    <i class="far fa-clock mr-1"></i>{{ $c->dias_espera <= 0 ? 'Pedida hoy' : 'Esperando ' . $c->dias_espera . ' día' . ($c->dias_espera == 1 ? '' : 's') }}
                </small>
                <div class="pr-acc">
                    <a href="{{ route('compras.recibir.form', $c) }}" class="btn btn-success btn-sm"><i class="fas fa-box-open mr-1"></i>Recibir</a>
                    <a href="{{ route('compras.show', $c) }}" class="btn btn-light btn-sm">Ver</a>
                </div>
            </div>
        @endforeach
    </div>
@endif

<div class="inv-chips">
    @foreach(\App\Http\Controllers\ComprasController::PERIODOS as $clave => $texto)
        <a href="{{ route('compras.index', $filtros + ['periodo' => $clave]) }}" class="inv-chip {{ $periodo === (string) $clave ? 'activo' : '' }}">{{ $texto }}</a>
    @endforeach
</div>

<form method="GET" action="{{ route('compras.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <input type="hidden" name="periodo" value="{{ $periodo }}">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" data-sugerencias="proveedores" autocomplete="off" class="form-control"
               placeholder="Proveedor, insumo, factura o #número…" value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Proveedor</label>
    <select name="id_proveedor" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach($proveedores as $p)
            <option value="{{ $p->id }}" {{ request('id_proveedor') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
        @endforeach
    </select>
    <label class="fb-label mb-0">Estado</label>
    <select name="estado" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach(\App\Http\Controllers\ComprasController::ESTADOS as $clave => $texto)
            <option value="{{ $clave }}" {{ request('estado') === $clave ? 'selected' : '' }}>{{ $texto }}</option>
        @endforeach
    </select>
    <details {{ !$periodo ? 'open' : '' }} class="d-inline-block">
        <summary class="btn btn-light btn-sm mb-0">Fechas exactas</summary>
        <span class="d-inline-flex align-items-center" style="gap:.35rem">
            <input type="date" name="desde" class="form-control" value="{{ $desde }}" onchange="this.form.periodo.value=''">
            <input type="date" name="hasta" class="form-control" value="{{ $hasta }}" onchange="this.form.periodo.value=''">
        </span>
    </details>
    <button class="btn btn-primary"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if($hayFiltros)
        <a href="{{ route('compras.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

<div class="d-flex justify-content-between align-items-center mb-2" style="font-size:.82rem;color:#8a8a9d">
    <span>{{ $filtrado['n'] }} compra{{ $filtrado['n'] == 1 ? '' : 's' }}{{ $hayFiltros ? ' con estos filtros' : '' }}</span>
    <span>Total (sin anuladas): <b class="text-body">S/ {{ number_format($filtrado['total'], 2) }}</b></span>
</div>

@if($compras->count())
    @foreach($compras as $c)
        @php
            $est = $nombreEstado($c);
            $insumos = $c->detalles->map(fn ($d) => $d->materia->nombre ?? '?');
        @endphp
        <div class="cp-fila {{ $est }}" data-href="{{ route('compras.show', $c) }}" role="link" tabindex="0" aria-label="Ver compra {{ $c->codigo }}">
            <div class="cf-ic {{ $est }}"><i class="fas {{ $iconoEstado[$est] }}"></i></div>
            <div style="min-width:0">
                <div class="cf-t">{{ $c->proveedor->nombre ?? '—' }} <small class="text-muted font-weight-normal">{{ $c->codigo }}</small></div>
                <div class="cf-s">{{ $insumos->take(3)->join(', ') }}{{ $insumos->count() > 3 ? ' y ' . ($insumos->count() - 3) . ' más' : '' }}</div>
            </div>
            <div class="cf-dato">
                <small>Fecha</small>
                <span>{{ $c->fecha_compra->locale('es')->translatedFormat('d M Y') }}</span>
            </div>
            <div class="cf-dato">
                <small>Factura / registró</small>
                <span>{{ $c->numero_doc ?: 'Sin factura' }}</span>
                <div style="font-size:.72rem;color:#8a8a9d">{{ $c->usuario->apodo ?? $c->usuario->nombre ?? '—' }}</div>
            </div>
            <div class="cf-total">
                S/ {{ number_format($c->total, 2) }}
                <div style="font-size:.72rem;font-weight:600;color:#8a8a9d">{{ $c->detalles_count }} insumo{{ $c->detalles_count == 1 ? '' : 's' }}</div>
            </div>
            <div class="cf-acc">
                <span class="cp-estado {{ $est }}">
                    {{ $textoEstado[$est] }}@if($est === 'atrasada') · {{ $c->dias_espera }} días @endif
                </span>
                @if($c->estado === 'pendiente')
                    <a href="{{ route('compras.recibir.form', $c) }}" class="btn btn-success btn-sm"><i class="fas fa-box-open mr-1"></i>Recibir</a>
                @endif
            </div>
        </div>
    @endforeach
    <div class="mt-3">{{ $compras->links() }}</div>
@else
    <div class="empty-state">
        <i class="fas fa-shopping-cart"></i>
        <p>{{ $hayFiltros ? 'No hay compras con estos filtros.' : 'Todavía no hay compras registradas.' }}</p>
        @if($hayFiltros)
            <a href="{{ route('compras.index') }}" class="btn btn-light">Ver todas</a>
        @else
            <a href="{{ route('compras.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Registrar la primera</a>
        @endif
    </div>
@endif
@endsection

@push('scripts')
<script>
// Toda la fila abre la compra (menos los botones que tiene dentro)
document.querySelectorAll('.cp-fila[data-href]').forEach(function (f) {
    f.style.cursor = 'pointer';
    f.addEventListener('click', function (e) { if (!e.target.closest('a, button')) location.href = f.dataset.href; });
    f.addEventListener('keydown', function (e) { if (e.key === 'Enter' && e.target === f) location.href = f.dataset.href; });
});
</script>
@endpush
