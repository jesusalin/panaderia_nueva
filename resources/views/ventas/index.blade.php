@extends('layouts.app')
@section('title', 'Ventas')
@section('breadcrumb') <li class="breadcrumb-item active">Ventas</li> @endsection

@push('styles')
<style>
    .venta-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .venta-stat {
        background: #fff; border-radius: 12px; padding: 1rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .8rem;
    }
    body.dark-mode .venta-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .venta-stat .vs-icon {
        width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .venta-stat .vs-value { font-size: 1.3rem; font-weight: 800; color: #1a1a2e; line-height: 1.15; }
    body.dark-mode .venta-stat .vs-value { color: #f0f0f7; }
    .venta-stat .vs-value.vs-value-sm { font-size: 1rem; }
    .venta-stat .vs-label { font-size: .74rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .2rem; }
    .venta-stat.cant     { border-left-color: #1e8e5a; }
    .venta-stat.cant .vs-icon     { background: rgba(30,142,90,.12); color: #1e8e5a; }
    .venta-stat.ingresos { border-left-color: #2ecc71; }
    .venta-stat.ingresos .vs-icon { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .venta-stat.ticket   { border-left-color: #3498db; }
    .venta-stat.ticket .vs-icon   { background: rgba(52,152,219,.14); color: #2170a3; }
    .venta-stat.metodo   { border-left-color: #b5451b; }
    .venta-stat.metodo .vs-icon   { background: rgba(181,69,27,.12); color: #b5451b; }
    @media (max-width: 768px) { .venta-stats { grid-template-columns: repeat(2, 1fr); } }

    /* Chip del número de comprobante, look de ticket */
    .ticket-chip {
        font-family: 'Courier New', monospace; font-weight: 800; letter-spacing: .02em;
        background: #f7f5f3; border: 1px dashed #d8cfc6; border-radius: 6px;
        padding: .2rem .55rem; font-size: .82rem; color: #1a1a2e; white-space: nowrap;
    }
    body.dark-mode .ticket-chip { background: #24243b; border-color: #33334d; color: #e4e4ef; }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-cash-register mr-2 text-success"></i>Listado de Ventas</h2>
        <p>Historial de ventas realizadas a clientes y puntos de distribución</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('ventas.create') }}" class="btn btn-success">
            <i class="fas fa-plus mr-1"></i>Nueva Venta
        </a>
    </div>
</div>

@php
    $iconosPago = ['efectivo'=>'fa-money-bill-wave','yape'=>'fa-mobile-screen','plin'=>'fa-mobile-screen','tarjeta'=>'fa-credit-card'];
@endphp

<div class="venta-stats">
    <div class="venta-stat cant">
        <div class="vs-icon"><i class="fas fa-receipt"></i></div>
        <div><div class="vs-value">{{ $stats['ventas_hoy'] }}</div><div class="vs-label">Ventas hoy</div></div>
    </div>
    <div class="venta-stat ingresos">
        <div class="vs-icon"><i class="fas fa-sack-dollar"></i></div>
        <div><div class="vs-value">S/ {{ number_format($stats['ingresos_hoy'], 2) }}</div><div class="vs-label">Ingresos hoy</div></div>
    </div>
    <div class="venta-stat ticket">
        <div class="vs-icon"><i class="fas fa-calculator"></i></div>
        <div><div class="vs-value">S/ {{ number_format($stats['ticket_prom'], 2) }}</div><div class="vs-label">Ticket promedio</div></div>
    </div>
    <div class="venta-stat metodo">
        <div class="vs-icon"><i class="fas {{ $iconosPago[$stats['pago_top']->tipo_pago ?? ''] ?? 'fa-coins' }}"></i></div>
        <div><div class="vs-value vs-value-sm">{{ $stats['pago_top'] ? ucfirst($stats['pago_top']->tipo_pago) : '—' }}</div><div class="vs-label">Pago más usado hoy</div></div>
    </div>
</div>

@if($ventas->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Nro.</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th>
                <th>Pago</th><th class="text-right">Total</th><th class="text-center">Estado</th><th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ventas as $v)
            @php
                $iconoPago = $iconosPago[$v->tipo_pago] ?? 'fa-coins';
            @endphp
            <tr>
                <td><span class="ticket-chip">{{ $v->numero_venta }}</span></td>
                <td>{{ $v->fecha_venta->format('d/m/Y H:i') }}</td>
                <td>{{ $v->cliente->nombre ?? 'General' }}</td>
                <td>{{ $v->usuario->nombre }}</td>
                <td><span class="badge-soft badge-soft-secondary"><i class="fas {{ $iconoPago }} mr-1"></i>{{ ucfirst($v->tipo_pago) }}</span></td>
                <td class="text-right font-weight-bold">S/ {{ number_format($v->total, 2) }}</td>
                <td class="text-center">
                    <span class="badge-soft {{ $v->estado === 'completada' ? 'badge-soft-success' : 'badge-soft-danger' }}">
                        {{ ucfirst($v->estado) }}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('ventas.show', $v) }}" class="btn btn-icon btn-info js-ver-detalle" data-titulo-detalle="Comprobante {{ $v->numero_venta }}" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($v->estado !== 'anulada')
                        <form action="{{ route('ventas.anular', $v) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Anular esta venta?"
                            data-confirm="La venta &quot;{{ $v->numero_venta }}&quot; quedará anulada y el stock de sus productos se devolverá al inventario. La venta se conserva en el historial. Esta acción no se puede deshacer.">
                            @csrf @method('PUT')
                            <button class="btn btn-icon btn-warning" title="Anular venta"><i class="fas fa-ban"></i></button>
                        </form>
                        @endif
                        <form action="{{ route('ventas.destroy', $v) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Borrar esta venta?"
                            data-confirm="La venta &quot;{{ $v->numero_venta }}&quot; se eliminará por completo del sistema{{ $v->estado === 'completada' ? ' y el stock de sus productos se devolverá al inventario' : '' }}. Esta acción NO se puede deshacer.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Borrar venta"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $ventas->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-cash-register"></i>
    <p>Todavía no tienes ventas registradas</p>
    <a href="{{ route('ventas.create') }}" class="btn btn-success"><i class="fas fa-plus mr-1"></i>Registrar la primera</a>
</div>
@endif

@endsection
