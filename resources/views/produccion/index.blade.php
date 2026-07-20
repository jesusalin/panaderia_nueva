@extends('layouts.app')
@section('title', 'Producción')
@section('breadcrumb')
    <li class="breadcrumb-item active">Producción</li>
@endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-industry mr-2 text-warning"></i>Registro de Producción</h2>
        <p>Producciones registradas, que descuentan automáticamente los insumos de la receta</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('produccion.recetas') }}" class="btn btn-outline-secondary">
            <i class="fas fa-book mr-1"></i>Recetas
        </a>
        <a href="{{ route('produccion.create') }}" class="btn btn-warning">
            <i class="fas fa-plus mr-1"></i>Nueva Producción
        </a>
    </div>
</div>

@if($producciones->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th class="text-center">Cantidad</th>
                <th>Fecha</th>
                <th>Registrado por</th>
                <th>Observación</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($producciones as $p)
            <tr>
                <td class="text-muted small">#{{ $p->id }}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="row-icon"><i class="fas fa-bread-slice"></i></div>
                        <div class="ml-2 row-title">{{ $p->producto->nombre }}</div>
                    </div>
                </td>
                <td class="text-center"><span class="badge-soft badge-soft-success">{{ $p->cantidad }} und.</span></td>
                <td>{{ $p->fecha->format('d/m/Y') }}</td>
                <td>{{ $p->usuario->nombre ?? '—' }}</td>
                <td class="text-muted small">{{ $p->observacion ?? '—' }}</td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('produccion.show', $p) }}" class="btn btn-icon btn-info" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($producciones->hasPages())
    <div class="card-footer bg-white">{{ $producciones->links() }}</div>
    @endif
</div>
@else
<div class="empty-state">
    <i class="fas fa-industry"></i>
    <p>No hay producciones registradas aún</p>
    <a href="{{ route('produccion.create') }}" class="btn btn-warning"><i class="fas fa-plus mr-1"></i>Registrar la primera</a>
</div>
@endif

@endsection
