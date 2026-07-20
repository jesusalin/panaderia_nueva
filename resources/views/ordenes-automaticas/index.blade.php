@extends('layouts.app')
@section('title', 'Órdenes Automáticas')
@section('breadcrumb') <li class="breadcrumb-item active">Órdenes Automáticas</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-robot mr-2 text-primary"></i>Órdenes Automáticas de Reposición</h2>
        <p>El sistema detecta insumos con stock bajo y sugiere cuánto reponer</p>
    </div>
    <div class="toolbar-actions">
        <form action="{{ route('ordenes-automaticas.generar') }}" method="POST">
            @csrf
            <button class="btn btn-primary">
                <i class="fas fa-sync-alt mr-1"></i>Revisar Stock y Generar Órdenes
            </button>
        </form>
    </div>
</div>

<div class="alert alert-info alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
    <i class="fas fa-info-circle mr-1"></i>
    El sistema revisa todos los insumos activos y genera automáticamente una orden
    cuando el stock llega o baja del mínimo configurado. Cada orden se puede
    <strong>convertir en compra</strong> (requiere proveedor asignado al insumo) o
    <strong>descartar</strong> si aún no se necesita reabastecer.
</div>

@if($ordenes->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
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
            @foreach($ordenes as $o)
            <tr>
                <td class="small text-muted">{{ $o->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="row-icon"><i class="fas fa-wheat-awn"></i></div>
                        <div class="ml-2 row-title">{{ $o->materia->nombre }}</div>
                    </div>
                </td>
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
                        <span class="badge-soft badge-soft-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Sin asignar</span>
                    @endif
                </td>
                <td class="text-center">
                    @php
                        $badge = ['pendiente'=>'badge-soft-warning','convertida'=>'badge-soft-success','descartada'=>'badge-soft-secondary'][$o->estado];
                    @endphp
                    <span class="badge-soft {{ $badge }}">{{ ucfirst($o->estado) }}</span>
                </td>
                <td class="text-center">
                    @if($o->estado === 'pendiente')
                        <div class="btn-icon-group">
                            <form action="{{ route('ordenes-automaticas.convertir', $o) }}" method="POST" class="js-confirm"
                                data-confirm-title="¿Generar compra?"
                                data-confirm="Se creará una compra a {{ $o->proveedor->nombre ?? 'este proveedor' }} por {{ number_format($o->cantidad_sugerida, 3) }} {{ $o->materia->unidad->abreviatura }} de {{ $o->materia->nombre }}.">
                                @csrf
                                <button class="btn btn-icon btn-success" title="Convertir en compra">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                            </form>
                            <form action="{{ route('ordenes-automaticas.descartar', $o) }}" method="POST" class="js-confirm"
                                data-confirm-title="¿Descartar orden?"
                                data-confirm="No se generará ninguna compra para {{ $o->materia->nombre }} por ahora.">
                                @csrf
                                <button class="btn btn-icon btn-secondary" title="Descartar">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                        </div>
                    @elseif($o->estado === 'convertida')
                        <a href="{{ route('compras.show', $o->id_compra) }}" class="btn btn-sm btn-info">
                            <i class="fas fa-eye mr-1"></i>Ver compra
                        </a>
                    @else
                        <span class="text-muted small">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $ordenes->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-robot"></i>
    <p>No hay órdenes automáticas generadas todavía</p>
</div>
@endif

@endsection
