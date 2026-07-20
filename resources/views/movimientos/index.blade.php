@extends('layouts.app')
@section('title', 'Movimientos de Materia Prima')
@section('breadcrumb') <li class="breadcrumb-item active">Movimientos de Materia Prima</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-exchange-alt mr-2 text-info"></i>Movimientos de Materia Prima</h2>
        <p>Historial de entradas, salidas y ajustes de insumos</p>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" class="form-inline flex-wrap" style="gap:.6rem" onsubmit="TiempoOperacion.marcarInicio('verificacion_stock')">
        <span class="fb-label"><i class="fas fa-filter mr-1"></i>Filtrar</span>
        <select name="id_materia" class="form-control">
            <option value="">Todos los ingredientes</option>
            @foreach($materias as $m)
                <option value="{{ $m->id }}" {{ request('id_materia') == $m->id ? 'selected' : '' }}>
                    {{ $m->nombre }}
                </option>
            @endforeach
        </select>
        <select name="tipo" class="form-control">
            <option value="">Todos los tipos</option>
            <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entradas</option>
            <option value="salida"  {{ request('tipo') == 'salida'  ? 'selected' : '' }}>Salidas</option>
            <option value="ajuste"  {{ request('tipo') == 'ajuste'  ? 'selected' : '' }}>Ajustes</option>
        </select>
        <button class="btn btn-primary"><i class="fas fa-search mr-1"></i>Filtrar</button>
        <a href="{{ route('movimientos.index') }}" class="btn btn-light">Limpiar</a>
    </form>
</div>

@if($movimientos->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Ingrediente</th>
                <th class="text-center">Tipo</th>
                <th>Motivo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Stock Antes</th>
                <th class="text-center">Stock Después</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $mov)
            @php
                $tipoBadge = ['entrada'=>'badge-soft-success','salida'=>'badge-soft-danger','ajuste'=>'badge-soft-warning'][$mov->tipo];
                $tipoIcon  = ['entrada'=>'arrow-up','salida'=>'arrow-down','ajuste'=>'sync'][$mov->tipo];
            @endphp
            <tr>
                <td class="text-muted">{{ \Carbon\Carbon::parse($mov->created_at)->format('d/m/Y H:i') }}</td>
                <td class="row-title">{{ $mov->materia->nombre }}</td>
                <td class="text-center">
                    <span class="badge-soft {{ $tipoBadge }}">
                        <i class="fas fa-{{ $tipoIcon }} mr-1"></i>{{ ucfirst($mov->tipo) }}
                    </span>
                </td>
                <td>{{ ucfirst(str_replace('_',' ',$mov->motivo)) }}</td>
                <td class="text-center font-weight-bold {{ $mov->tipo === 'entrada' ? 'text-success' : ($mov->tipo === 'salida' ? 'text-danger' : 'text-warning') }}">
                    {{ $mov->tipo === 'entrada' ? '+' : ($mov->tipo === 'salida' ? '-' : '±') }}
                    {{ number_format($mov->cantidad, 3) }}
                    <small class="text-muted">{{ $mov->materia->unidad->abreviatura }}</small>
                </td>
                <td class="text-center">{{ number_format($mov->stock_antes, 3) }}</td>
                <td class="text-center font-weight-bold">{{ number_format($mov->stock_despues, 3) }}</td>
                <td>{{ $mov->usuario->nombre ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $movimientos->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-exchange-alt"></i>
    <p>No hay movimientos registrados con este filtro</p>
</div>
@endif

@endsection

@push('scripts')
<script>
TiempoOperacion.registrarFin('verificacion_stock');
</script>
@endpush
