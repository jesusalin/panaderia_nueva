@extends('layouts.app')
@section('title', 'Compra #' . $compra->id)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>
    <li class="breadcrumb-item active">Compra #{{ $compra->id }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-alt mr-2 text-warning"></i>Detalle de Compra #{{ $compra->id }}</h5>
                @if($compra->estado === 'pendiente')
                <form action="{{ route('compras.recibir', $compra) }}" method="POST"
                    onsubmit="return confirm('¿Confirmar recepción? Esto actualizará el stock de materia prima.')">
                    @csrf @method('PUT')
                    <button class="btn btn-success">
                        <i class="fas fa-check-circle mr-1"></i>Marcar como Recibida
                    </button>
                </form>
                @endif
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-6">
                        <p class="mb-1"><strong>Proveedor:</strong> {{ $compra->proveedor->nombre }}</p>
                        <p class="mb-1"><strong>Registrado por:</strong> {{ $compra->usuario->nombre }}</p>
                        <p class="mb-1"><strong>Nro. Documento:</strong> {{ $compra->numero_doc ?? '—' }}</p>
                    </div>
                    <div class="col-6 text-right">
                        <p class="mb-1"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($compra->fecha_compra)->format('d/m/Y') }}</p>
                        @php $badge = ['pendiente'=>'warning','recibida'=>'success','anulada'=>'danger'][$compra->estado]; @endphp
                        <span class="badge badge-{{ $badge }} badge-lg" style="font-size:.9rem;padding:.4em .8em">
                            {{ strtoupper($compra->estado) }}
                        </span>
                    </div>
                </div>

                @if($compra->observaciones)
                <div class="alert alert-light">{{ $compra->observaciones }}</div>
                @endif

                <table class="table table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Ingrediente</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Precio Unit.</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($compra->detalles as $d)
                        <tr>
                            <td>{{ $d->materia->nombre }}</td>
                            <td class="text-center">{{ number_format($d->cantidad, 3) }} {{ $d->materia->unidad->abreviatura }}</td>
                            <td class="text-right">S/ {{ number_format($d->precio_unitario, 2) }}</td>
                            <td class="text-right">S/ {{ number_format($d->subtotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-right">Subtotal:</td><td class="text-right">S/ {{ number_format($compra->subtotal, 2) }}</td></tr>
                        <tr class="text-muted"><td colspan="3" class="text-right">IGV (18%):</td><td class="text-right">S/ {{ number_format($compra->igv, 2) }}</td></tr>
                        <tr class="font-weight-bold"><td colspan="3" class="text-right h5">TOTAL:</td><td class="text-right h5 text-warning">S/ {{ number_format($compra->total, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white"><h6 class="mb-0">Acciones</h6></div>
            <div class="card-body">
                <a href="{{ route('compras.create') }}" class="btn btn-warning btn-block">
                    <i class="fas fa-plus mr-2"></i>Nueva compra
                </a>
                <a href="{{ route('compras.index') }}" class="btn btn-light btn-block">
                    <i class="fas fa-list mr-2"></i>Ver todas
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
