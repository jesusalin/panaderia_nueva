@extends('layouts.app')
@section('title', 'Ventas')
@section('breadcrumb') <li class="breadcrumb-item active">Ventas</li> @endsection

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
                $iconoPago = ['efectivo'=>'fa-money-bill-wave','yape'=>'fa-mobile-screen','tarjeta'=>'fa-credit-card'][$v->tipo_pago] ?? 'fa-coins';
            @endphp
            <tr>
                <td class="row-title">{{ $v->numero_venta }}</td>
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
