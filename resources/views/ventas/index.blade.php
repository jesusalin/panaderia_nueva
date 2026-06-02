@extends('layouts.app')
@section('title', 'Ventas')
@section('breadcrumb') <li class="breadcrumb-item active">Ventas</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-cash-register mr-2 text-success"></i>Listado de Ventas</h5>
        <a href="{{ route('ventas.create') }}" class="btn btn-success">
            <i class="fas fa-plus mr-1"></i>Nueva Venta
        </a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Nro.</th><th>Fecha</th><th>Cliente</th><th>Vendedor</th>
                    <th>Pago</th><th>Total</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ventas as $v)
                <tr>
                    <td><strong>{{ $v->numero_venta }}</strong></td>
                    <td>{{ $v->fecha_venta->format('d/m/Y H:i') }}</td>
                    <td>{{ $v->cliente->nombre ?? 'General' }}</td>
                    <td>{{ $v->usuario->nombre }}</td>
                    <td><span class="badge badge-light">{{ ucfirst($v->tipo_pago) }}</span></td>
                    <td><strong>S/ {{ number_format($v->total, 2) }}</strong></td>
                    <td>
                        <span class="badge badge-{{ $v->estado === 'completada' ? 'success' : 'danger' }}">
                            {{ ucfirst($v->estado) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('ventas.show', $v) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No hay ventas registradas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $ventas->links() }}</div>
</div>
@endsection
