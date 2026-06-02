@extends('layouts.app')
@section('title', 'Compras')
@section('breadcrumb') <li class="breadcrumb-item active">Compras</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-shopping-cart mr-2 text-warning"></i>Compras</h5>
        <a href="{{ route('compras.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nueva Compra
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
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
                @forelse($compras as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td><strong>{{ $c->proveedor->nombre }}</strong></td>
                    <td>{{ \Carbon\Carbon::parse($c->fecha_compra)->format('d/m/Y') }}</td>
                    <td>{{ $c->numero_doc ?? '—' }}</td>
                    <td class="text-right font-weight-bold">S/ {{ number_format($c->total, 2) }}</td>
                    <td class="text-center">
                        @php
                            $badge = ['pendiente'=>'warning','recibida'=>'success','anulada'=>'danger'][$c->estado];
                        @endphp
                        <span class="badge badge-{{ $badge }}">{{ ucfirst($c->estado) }}</span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('compras.show', $c) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No hay compras registradas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $compras->links() }}</div>
</div>
@endsection
