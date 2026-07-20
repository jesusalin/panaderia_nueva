@extends('layouts.app')
@section('title', 'Compras')
@section('breadcrumb') <li class="breadcrumb-item active">Compras</li> @endsection

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

@if($compras->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>#</th>
                <th>Proveedor</th>
                <th>Fecha</th>
                <th>Nro. Doc.</th>
                <th class="text-right">Total</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($compras as $c)
            @php
                $badge = ['pendiente'=>'badge-soft-warning','recibida'=>'badge-soft-success','anulada'=>'badge-soft-danger'][$c->estado];
                $icono = ['pendiente'=>'fa-clock','recibida'=>'fa-check','anulada'=>'fa-xmark'][$c->estado];
            @endphp
            <tr>
                <td class="text-muted">#{{ $c->id }}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="row-icon"><i class="fas fa-truck"></i></div>
                        <div class="ml-2 row-title">{{ $c->proveedor->nombre }}</div>
                    </div>
                </td>
                <td>{{ \Carbon\Carbon::parse($c->fecha_compra)->format('d/m/Y') }}</td>
                <td>{{ $c->numero_doc ?? '—' }}</td>
                <td class="text-right font-weight-bold">S/ {{ number_format($c->total, 2) }}</td>
                <td class="text-center">
                    <span class="badge-soft {{ $badge }}"><i class="fas {{ $icono }} mr-1"></i>{{ ucfirst($c->estado) }}</span>
                </td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('compras.show', $c) }}" class="btn btn-icon btn-info" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $compras->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-shopping-cart"></i>
    <p>Todavía no tienes compras registradas</p>
    <a href="{{ route('compras.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Registrar la primera</a>
</div>
@endif

@endsection
