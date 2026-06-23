@extends('layouts.app')
@section('title', 'Órdenes Automáticas')
@section('breadcrumb') <li class="breadcrumb-item active">Órdenes Automáticas</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-robot mr-2 text-primary"></i>Órdenes Automáticas de Reposición</h5>
        <form action="{{ route('ordenes-automaticas.generar') }}" method="POST">
            @csrf
            <button class="btn btn-primary">
                <i class="fas fa-sync-alt mr-1"></i>Revisar Stock y Generar Órdenes
            </button>
        </form>
    </div>

    <div class="card-body border-bottom">
        <div class="alert alert-info alert-dismissible fade show mb-0">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <i class="fas fa-info-circle mr-1"></i>
            El sistema revisa todos los insumos activos y genera automáticamente una orden
            cuando el stock llega o baja del mínimo configurado. Cada orden se puede
            <strong>convertir en compra</strong> (requiere proveedor asignado al insumo) o
            <strong>descartar</strong> si aún no se necesita reabastecer.
        </div>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Fecha</th>
                    <th>Insumo</th>
                    <th class="text-right">Stock al Generar</th>
                    <th class="text-right">Stock Mínimo</th>
                    <th class="text-right">Cant. Sugerida</th>
                    <th>Proveedor</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ordenes as $o)
                <tr>
                    <td class="small">{{ $o->created_at->format('d/m/Y H:i') }}</td>
                    <td><strong>{{ $o->materia->nombre }}</strong></td>
                    <td class="text-right text-danger font-weight-bold">
                        {{ number_format($o->stock_al_generar, 3) }} {{ $o->materia->unidad->abreviatura }}
                    </td>
                    <td class="text-right text-muted">
                        {{ number_format($o->stock_minimo, 3) }} {{ $o->materia->unidad->abreviatura }}
                    </td>
                    <td class="text-right font-weight-bold text-success">
                        {{ number_format($o->cantidad_sugerida, 3) }} {{ $o->materia->unidad->abreviatura }}
                    </td>
                    <td>
                        @if($o->proveedor)
                            {{ $o->proveedor->nombre }}
                        @else
                            <span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Sin asignar</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @php
                            $badge = ['pendiente'=>'warning','convertida'=>'success','descartada'=>'secondary'][$o->estado];
                        @endphp
                        <span class="badge badge-{{ $badge }}">{{ ucfirst($o->estado) }}</span>
                    </td>
                    <td class="text-center">
                        @if($o->estado === 'pendiente')
                            <form action="{{ route('ordenes-automaticas.convertir', $o) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-success" title="Convertir en compra"
                                        onclick="return confirm('¿Generar una compra con esta orden?')">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                            </form>
                            <form action="{{ route('ordenes-automaticas.descartar', $o) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-secondary" title="Descartar"
                                        onclick="return confirm('¿Descartar esta orden?')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        @elseif($o->estado === 'convertida')
                            <a href="{{ route('compras.show', $o->id_compra) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Ver compra
                            </a>
                        @else
                            <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No hay órdenes automáticas generadas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $ordenes->links() }}</div>
</div>
@endsection
