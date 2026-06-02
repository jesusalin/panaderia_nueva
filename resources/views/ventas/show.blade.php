@extends('layouts.app')
@section('title', 'Venta ' . $venta->numero_venta)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ventas.index') }}">Ventas</a></li>
    <li class="breadcrumb-item active">{{ $venta->numero_venta }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-file-invoice mr-2 text-success"></i>
                    Comprobante {{ $venta->numero_venta }}
                </h5>
                <div>
                    @if($venta->estado === 'completada')
                    <form action="{{ route('ventas.anular', $venta) }}" method="POST" class="d-inline"
                        onsubmit="return confirm('¿Anular esta venta?')">
                        @csrf @method('PUT')
                        <button class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-ban mr-1"></i>Anular
                        </button>
                    </form>
                    @endif
                    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary ml-1">
                        <i class="fas fa-print mr-1"></i>Imprimir
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <p class="mb-1"><strong>Cliente:</strong> {{ $venta->cliente->nombre ?? 'General' }}</p>
                        <p class="mb-1"><strong>Vendedor:</strong> {{ $venta->usuario->nombre }}</p>
                    </div>
                    <div class="col-6 text-right">
                        <p class="mb-1"><strong>Fecha:</strong> {{ $venta->fecha_venta->format('d/m/Y H:i') }}</p>
                        <p class="mb-1"><strong>Pago:</strong> {{ ucfirst($venta->tipo_pago) }}</p>
                        <span class="badge badge-{{ $venta->estado === 'completada' ? 'success' : 'danger' }} badge-lg">
                            {{ strtoupper($venta->estado) }}
                        </span>
                    </div>
                </div>

                <table class="table table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-right">P. Unit.</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($venta->detalles as $d)
                        <tr>
                            <td>{{ $d->producto->nombre }}</td>
                            <td class="text-center">{{ $d->cantidad }}</td>
                            <td class="text-right">S/ {{ number_format($d->precio_unitario, 2) }}</td>
                            <td class="text-right">S/ {{ number_format($d->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right">Subtotal:</td>
                            <td class="text-right">S/ {{ number_format($venta->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right text-muted">IGV (18%):</td>
                            <td class="text-right text-muted">S/ {{ number_format($venta->igv, 2) }}</td>
                        </tr>
                        <tr class="font-weight-bold">
                            <td colspan="3" class="text-right h5">TOTAL:</td>
                            <td class="text-right h5 text-success">S/ {{ number_format($venta->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Acciones rápidas</h6></div>
            <div class="card-body">
                <a href="{{ route('ventas.create') }}" class="btn btn-success btn-block">
                    <i class="fas fa-plus mr-2"></i>Nueva venta
                </a>
                <a href="{{ route('ventas.index') }}" class="btn btn-light btn-block">
                    <i class="fas fa-list mr-2"></i>Ver todas las ventas
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
