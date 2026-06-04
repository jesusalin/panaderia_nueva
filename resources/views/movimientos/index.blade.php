@extends('layouts.app')
@section('title', 'Movimientos de Materia Prima')
@section('breadcrumb') <li class="breadcrumb-item active">Movimientos</li> @endsection

@section('content')
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-exchange-alt mr-2 text-info"></i>Movimientos de Materia Prima</h5>
    </div>
    {{-- Filtros --}}
    <div class="card-body border-bottom bg-light py-2">
        <form method="GET" class="form-inline">
            <select name="id_materia" class="form-control form-control-sm mr-2">
                <option value="">Todos los ingredientes</option>
                @foreach($materias as $m)
                    <option value="{{ $m->id }}" {{ request('id_materia') == $m->id ? 'selected' : '' }}>
                        {{ $m->nombre }}
                    </option>
                @endforeach
            </select>
            <select name="tipo" class="form-control form-control-sm mr-2">
                <option value="">Todos los tipos</option>
                <option value="entrada" {{ request('tipo') === 'entrada' ? 'selected' : '' }}>Entrada</option>
                <option value="salida"  {{ request('tipo') === 'salida'  ? 'selected' : '' }}>Salida</option>
                <option value="ajuste"  {{ request('tipo') === 'ajuste'  ? 'selected' : '' }}>Ajuste</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary mr-2">
                <i class="fas fa-filter mr-1"></i>Filtrar
            </button>
            <a href="{{ route('movimientos.index') }}" class="btn btn-sm btn-light">Limpiar</a>
        </form>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Fecha</th>
                    <th>Ingrediente</th>
                    <th class="text-center">Tipo</th>
                    <th class="text-center">Motivo</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Stock Antes</th>
                    <th class="text-right">Stock Después</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movimientos as $mov)
                <tr>
                    <td class="small">{{ \Carbon\Carbon::parse($mov->created_at)->format('d/m/Y H:i') }}</td>
                    <td><strong>{{ $mov->materia->nombre }}</strong></td>
                    <td class="text-center">
                        @php
                            $tipoBadge = ['entrada'=>'success','salida'=>'danger','ajuste'=>'warning'][$mov->tipo];
                            $tipoIcon  = ['entrada'=>'arrow-down','salida'=>'arrow-up','ajuste'=>'sync'][$mov->tipo];
                        @endphp
                        <span class="badge badge-{{ $tipoBadge }}">
                            <i class="fas fa-{{ $tipoIcon }} mr-1"></i>{{ ucfirst($mov->tipo) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light">{{ ucfirst(str_replace('_',' ',$mov->motivo)) }}</span>
                    </td>
                    <td class="text-right font-weight-bold">
                        <span class="text-{{ $mov->tipo === 'entrada' ? 'success' : ($mov->tipo === 'salida' ? 'danger' : 'warning') }}">
                            {{ $mov->tipo === 'entrada' ? '+' : ($mov->tipo === 'salida' ? '-' : '±') }}
                            {{ number_format($mov->cantidad, 3) }}
                        </span>
                        <small class="text-muted">{{ $mov->materia->unidad->abreviatura }}</small>
                    </td>
                    <td class="text-right text-muted small">{{ number_format($mov->stock_antes, 3) }}</td>
                    <td class="text-right small font-weight-bold">{{ number_format($mov->stock_despues, 3) }}</td>
                    <td class="small">{{ $mov->usuario->nombre }}</td>
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
