@extends('layouts.app')
@section('title', 'Compras')
@section('breadcrumb') <li class="breadcrumb-item active">Compras</li> @endsection

@push('styles')
<style>
    .compra-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .compra-stat {
        background: #fff; border-radius: 12px; padding: 1rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .8rem;
    }
    body.dark-mode .compra-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .compra-stat .cs-icon {
        width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .compra-stat .cs-value { font-size: 1.35rem; font-weight: 800; color: #1a1a2e; line-height: 1; }
    body.dark-mode .compra-stat .cs-value { color: #f0f0f7; }
    .compra-stat .cs-label { font-size: .74rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .2rem; }
    .compra-stat.total      { border-left-color: #3498db; }
    .compra-stat.total .cs-icon      { background: rgba(52,152,219,.14); color: #2170a3; }
    .compra-stat.pendientes { border-left-color: #f39c12; }
    .compra-stat.pendientes .cs-icon { background: rgba(243,156,18,.14); color: #b9770e; }
    .compra-stat.recibidas  { border-left-color: #2ecc71; }
    .compra-stat.recibidas .cs-icon  { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .compra-stat.monto      { border-left-color: #b5451b; }
    .compra-stat.monto .cs-icon      { background: rgba(181,69,27,.12); color: #b5451b; }
    @media (max-width: 900px) { .compra-stats { grid-template-columns: repeat(2, 1fr); } }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-shopping-cart mr-2 text-warning"></i>Compras</h2>
        <p>Pedidos de materia prima realizados a tus proveedores</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('compras.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nueva Compra
        </a>
    </div>
</div>

<div class="compra-stats">
    <div class="compra-stat total">
        <div class="cs-icon"><i class="fas fa-shopping-cart"></i></div>
        <div><div class="cs-value">{{ $stats['total'] }}</div><div class="cs-label">Compras</div></div>
    </div>
    <div class="compra-stat pendientes">
        <div class="cs-icon"><i class="fas fa-clock"></i></div>
        <div><div class="cs-value">{{ $stats['pendientes'] }}</div><div class="cs-label">Por recibir</div></div>
    </div>
    <div class="compra-stat recibidas">
        <div class="cs-icon"><i class="fas fa-check-circle"></i></div>
        <div><div class="cs-value">{{ $stats['recibidas'] }}</div><div class="cs-label">Recibidas</div></div>
    </div>
    <div class="compra-stat monto">
        <div class="cs-icon"><i class="fas fa-coins"></i></div>
        <div><div class="cs-value">S/ {{ number_format($stats['monto_mes'], 2) }}</div><div class="cs-label">Gastado este mes</div></div>
    </div>
</div>

<form method="GET" action="{{ route('compras.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
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
        <option value="pendiente" {{ request('estado') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
        <option value="recibida"  {{ request('estado') === 'recibida'  ? 'selected' : '' }}>Recibida</option>
        <option value="anulada"   {{ request('estado') === 'anulada'   ? 'selected' : '' }}>Anulada</option>
    </select>
    <label class="fb-label mb-0">Desde</label>
    <input type="date" name="fecha_desde" class="form-control" style="width:auto;" value="{{ request('fecha_desde') }}">
    <label class="fb-label mb-0">Hasta</label>
    <input type="date" name="fecha_hasta" class="form-control" style="width:auto;" value="{{ request('fecha_hasta') }}">
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request()->anyFilled(['id_proveedor','estado','fecha_desde','fecha_hasta']))
        <a href="{{ route('compras.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($compras->count() > 0)
<div class="list-rows">
    @foreach($compras as $c)
    @php
        $iconoClase = ['pendiente'=>'warning','recibida'=>'success','anulada'=>'danger'][$c->estado];
        $icono = ['pendiente'=>'fa-clock','recibida'=>'fa-check','anulada'=>'fa-xmark'][$c->estado];
        $badge = ['pendiente'=>'badge-soft-warning','recibida'=>'badge-soft-success','anulada'=>'badge-soft-danger'][$c->estado];
    @endphp
    <div class="list-row">
        <div class="lr-icon {{ $iconoClase }}"><i class="fas {{ $icono }}"></i></div>
        <div class="lr-main">
            <div class="lr-title">{{ $c->proveedor->nombre }}</div>
            <div class="lr-subtitle">{{ $c->numero_doc ?? 'Sin documento' }} · #{{ $c->id }} · {{ $c->usuario->nombre ?? '—' }}</div>
        </div>
        <div class="lr-meta">
            <div class="lm-item">
                <span class="lm-label">Fecha</span>
                <span class="lm-value">{{ \Carbon\Carbon::parse($c->fecha_compra)->format('d/m/Y') }}</span>
            </div>
            <div class="lm-item">
                <span class="lm-label">Estado</span>
                <span class="badge-soft {{ $badge }}">{{ ucfirst($c->estado) }}</span>
            </div>
        </div>
        <div class="lr-side">
            <div class="lr-amount">S/ {{ number_format($c->total, 2) }}</div>
            @if($c->estado === 'pendiente')
            <form action="{{ route('compras.recibir', $c) }}" method="POST" class="js-confirm"
                data-confirm-title="¿Marcar como recibida?"
                data-confirm="Esto actualizará el stock de materia prima con lo comprado en el pedido #{{ $c->id }}.">
                @csrf @method('PUT')
                <button class="btn btn-icon btn-success" title="Marcar como recibida"><i class="fas fa-check"></i></button>
            </form>
            <form action="{{ route('compras.anular', $c) }}" method="POST" class="js-confirm"
                data-confirm-title="¿Anular esta compra?"
                data-confirm="Se cancelará el pedido #{{ $c->id }} a &quot;{{ $c->proveedor->nombre }}&quot;. Útil si se registró con el proveedor equivocado. No se puede deshacer.">
                @csrf @method('PUT')
                <button class="btn btn-icon btn-danger" title="Anular (registrada por error)"><i class="fas fa-ban"></i></button>
            </form>
            @endif
            <a href="{{ route('compras.show', $c) }}" class="btn btn-icon btn-info" title="Ver detalle">
                <i class="fas fa-eye"></i>
            </a>
        </div>
    </div>
    @endforeach
</div>
<div class="mt-3">{{ $compras->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-shopping-cart"></i>
    <p>No hay compras que coincidan con este filtro</p>
    <a href="{{ route('compras.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Registrar la primera</a>
</div>
@endif

@endsection
