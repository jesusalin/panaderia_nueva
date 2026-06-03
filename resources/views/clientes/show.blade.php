@extends('layouts.app')
@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar-lg mx-auto mb-3">
                    <span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-24">
                        {{ strtoupper(substr($cliente->nombre, 0, 1)) }}
                    </span>
                </div>
                <h5>{{ $cliente->nombre }}</h5>
                <span class="badge bg-info-subtle text-info">{{ ucfirst($cliente->tipo ?? 'particular') }}</span>
                <span class="badge bg-{{ ($cliente->estado ?? 'activo') === 'activo' ? 'success' : 'danger' }} ms-1">
                    {{ ucfirst($cliente->estado ?? 'activo') }}
                </span>
                <hr>
                <div class="text-start">
                    @if($cliente->ruc)
                        <p><strong>RUC:</strong> {{ $cliente->ruc }}</p>
                    @endif
                    @if($cliente->dni)
                        <p><strong>DNI:</strong> {{ $cliente->dni }}</p>
                    @endif
                    @if($cliente->telefono)
                        <p><strong>Teléfono:</strong> {{ $cliente->telefono }}</p>
                    @endif
                    @if($cliente->email)
                        <p><strong>Email:</strong> {{ $cliente->email }}</p>
                    @endif
                    @if($cliente->direccion)
                        <p><strong>Dirección:</strong> {{ $cliente->direccion }}</p>
                    @endif
                    @if($cliente->distrito)
                        <p><strong>Distrito:</strong> {{ $cliente->distrito }}</p>
                    @endif
                    @if($cliente->referencia)
                        <p><strong>Referencia:</strong> {{ $cliente->referencia }}</p>
                    @endif
                </div>
                <hr>
                <h6 class="text-muted">Total comprado</h6>
                <h4 class="text-success">S/. {{ number_format($totalComprado, 2) }}</h4>
                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-warning btn-sm mt-2">
                    <i class="ri-edit-line"></i> Editar
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5>Historial de Ventas</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>N° Venta</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Pago</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ventas as $venta)
                        <tr>
                            <td>{{ $venta->numero_venta }}</td>
                            <td>{{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}</td>
                            <td>S/. {{ number_format($venta->total, 2) }}</td>
                            <td>{{ ucfirst($venta->tipo_pago) }}</td>
                            <td>
                                <span class="badge bg-{{ $venta->estado === 'completada' ? 'success' : 'danger' }}">
                                    {{ ucfirst($venta->estado) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center">Sin ventas registradas</td></tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $ventas->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
