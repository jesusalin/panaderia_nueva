@extends('layouts.app')
@section('title', 'Producción')
@section('breadcrumb')
    <li class="breadcrumb-item active">Producción</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-industry mr-2 text-warning"></i>Registro de Producción</h4>
        <div>
            <a href="{{ route('produccion.recetas') }}" class="btn btn-outline-secondary mr-2">
                <i class="fas fa-book mr-1"></i>Recetas
            </a>
            <a href="{{ route('produccion.create') }}" class="btn btn-warning">
                <i class="fas fa-plus mr-1"></i>Nueva Producción
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Fecha</th>
                    <th>Registrado por</th>
                    <th>Observación</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($producciones as $p)
                <tr>
                    <td class="text-muted small">{{ $p->id }}</td>
                    <td class="font-weight-bold">{{ $p->producto->nombre }}</td>
                    <td><span class="badge badge-success">{{ $p->cantidad }} und.</span></td>
                    <td>{{ $p->fecha->format('d/m/Y') }}</td>
                    <td>{{ $p->usuario->nombre ?? '—' }}</td>
                    <td class="text-muted small">{{ $p->observacion ?? '—' }}</td>
                    <td>
                        <a href="{{ route('produccion.show', $p) }}" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-industry fa-2x mb-2 d-block"></i>
                        No hay producciones registradas aún.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($producciones->hasPages())
    <div class="card-footer">{{ $producciones->links() }}</div>
    @endif
</div>
@endsection
