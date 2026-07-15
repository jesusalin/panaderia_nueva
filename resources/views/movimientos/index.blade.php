@extends('layouts.app')
@section('title', 'Movimientos de Materia Prima')
@section('breadcrumb') <li class="breadcrumb-item active">Movimientos de Materia Prima</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-exchange-alt mr-2 text-info"></i>Movimientos de Materia Prima</h5>
    </div>

    {{-- Filtros --}}
    <div class="card-body border-bottom">
        <form method="GET" class="form-inline flex-wrap gap-2" onsubmit="TiempoOperacion.marcarInicio('verificacion_stock')">
            <select name="id_materia" class="form-control mr-2 mb-2">
                <option value="">Todos los ingredientes</option>
                @foreach($materias as $m)
                    <option value="{{ $m->id }}" {{ request('id_materia') == $m->id ? 'selected' : '' }}>
                        {{ $m->nombre }}
                    </option>
                @endforeach
            </select>
            <select name="tipo" class="form-control mr-2 mb-2">
                <option value="">Todos</option>
                <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entradas</option>
                <option value="salida"  {{ request('tipo') == 'salida'  ? 'selected' : '' }}>Salidas</option>
                <option value="ajuste"  {{ request('tipo') == 'ajuste'  ? 'selected' : '' }}>Ajustes</option>
            </select>
            <button class="btn btn-primary mb-2"><i class="fas fa-search mr-1"></i>Filtrar</button>
            <a href="{{ route('movimientos.index') }}" class="btn btn-secondary mb-2">Limpiar</a>
        </form>
    </div>

    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
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
                @forelse($movimientos as $mov)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($mov->created_at)->format('d/m/Y H:i') }}</td>
                    <td><strong>{{ $mov->materia->nombre }}</strong></td>
                    <td class="text-center">
                        @php
                            $tipoBadge = ['entrada'=>'success','salida'=>'danger','ajuste'=>'warning'][$mov->tipo];
                            $tipoIcon  = ['entrada'=>'arrow-up','salida'=>'arrow-down','ajuste'=>'sync'][$mov->tipo];
                        @endphp
                        <span class="badge badge-{{ $tipoBadge }}">
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
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No hay movimientos registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">{{ $movimientos->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
TiempoOperacion.registrarFin('verificacion_stock');
</script>
@endpush