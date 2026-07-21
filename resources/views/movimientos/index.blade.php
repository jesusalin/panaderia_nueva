@extends('layouts.app')
@section('title', 'Movimientos de Materia Prima')
@section('breadcrumb') <li class="breadcrumb-item active">Movimientos de Materia Prima</li> @endsection

@push('styles')
<style>
    .mov-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .mov-stat {
        background: #fff; border-radius: 12px; padding: 1rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .8rem;
    }
    body.dark-mode .mov-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .mov-stat .ms-icon {
        width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .mov-stat .ms-value { font-size: 1.35rem; font-weight: 800; color: #1a1a2e; line-height: 1; }
    body.dark-mode .mov-stat .ms-value { color: #f0f0f7; }
    .mov-stat .ms-label { font-size: .74rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .2rem; }
    .mov-stat.entradas { border-left-color: #2ecc71; }
    .mov-stat.entradas .ms-icon { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .mov-stat.salidas  { border-left-color: #e74c3c; }
    .mov-stat.salidas .ms-icon { background: rgba(231,76,60,.12); color: #c0392b; }
    .mov-stat.ajustes  { border-left-color: #f39c12; }
    .mov-stat.ajustes .ms-icon { background: rgba(243,156,18,.14); color: #b9770e; }
    .mov-stat.hoy      { border-left-color: #3498db; }
    .mov-stat.hoy .ms-icon { background: rgba(52,152,219,.14); color: #2170a3; }

    .table-modern .mov-materia { display: flex; align-items: center; gap: .6rem; }
    .table-modern .mov-materia-icon {
        width: 32px; height: 32px; border-radius: 8px; background: #f7f5f3; color: #b5451b;
        display: flex; align-items: center; justify-content: center; font-size: .82rem; flex-shrink: 0;
    }
    body.dark-mode .table-modern .mov-materia-icon { background: #24243b; color: #ff9d6e; }

    .mov-obs {
        display: inline-block; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        font-size: .82rem; color: #8a8a9d; vertical-align: middle;
    }
    body.dark-mode .mov-obs { color: #9a9ac0; }
    .mov-obs-empty { color: #ced4da; font-style: italic; }

    .mov-fecha .mf-dia { font-weight: 700; color: #1a1a2e; }
    body.dark-mode .mov-fecha .mf-dia { color: #f0f0f7; }
    .mov-fecha .mf-hora { display: block; font-size: .74rem; color: #adb5bd; }
    .mov-hoy-tag { font-size: .65rem; font-weight: 800; color: #2170a3; background: rgba(52,152,219,.14); padding: .05rem .4rem; border-radius: 8px; margin-left: .3rem; vertical-align: middle; }
    body.dark-mode .mov-hoy-tag { background: rgba(52,152,219,.2); color: #7ec3f5; }

    @media (max-width: 768px) { .mov-stats { grid-template-columns: repeat(2, 1fr); } }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-exchange-alt mr-2 text-info"></i>Movimientos de Materia Prima</h2>
        <p>Historial de entradas, salidas y ajustes de insumos</p>
    </div>
</div>

<div class="mov-stats">
    <div class="mov-stat entradas">
        <div class="ms-icon"><i class="fas fa-arrow-up"></i></div>
        <div><div class="ms-value">{{ $stats['entradas'] }}</div><div class="ms-label">Entradas</div></div>
    </div>
    <div class="mov-stat salidas">
        <div class="ms-icon"><i class="fas fa-arrow-down"></i></div>
        <div><div class="ms-value">{{ $stats['salidas'] }}</div><div class="ms-label">Salidas</div></div>
    </div>
    <div class="mov-stat ajustes">
        <div class="ms-icon"><i class="fas fa-sync"></i></div>
        <div><div class="ms-value">{{ $stats['ajustes'] }}</div><div class="ms-label">Ajustes</div></div>
    </div>
    <div class="mov-stat hoy">
        <div class="ms-icon"><i class="fas fa-calendar-day"></i></div>
        <div><div class="ms-value">{{ $stats['hoy'] }}</div><div class="ms-label">Hoy</div></div>
    </div>
</div>

<form method="GET" action="{{ route('movimientos.index') }}" class="filter-bar" onsubmit="TiempoOperacion.marcarInicio('verificacion_stock')">
    <label class="fb-label mb-0">Ingrediente</label>
    <select name="id_materia" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos los ingredientes</option>
        @foreach($materias as $m)
            <option value="{{ $m->id }}" {{ request('id_materia') == $m->id ? 'selected' : '' }}>{{ $m->nombre }}</option>
        @endforeach
    </select>
    <label class="fb-label mb-0">Tipo</label>
    <select name="tipo" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos los tipos</option>
        <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entradas</option>
        <option value="salida"  {{ request('tipo') == 'salida'  ? 'selected' : '' }}>Salidas</option>
        <option value="ajuste"  {{ request('tipo') == 'ajuste'  ? 'selected' : '' }}>Ajustes</option>
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('id_materia') || request('tipo'))
        <a href="{{ route('movimientos.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

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
                <th>Observación</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $mov)
            @php
                $tipoBadge = ['entrada'=>'badge-soft-success','salida'=>'badge-soft-danger','ajuste'=>'badge-soft-warning'][$mov->tipo];
                $tipoIcon  = ['entrada'=>'arrow-up','salida'=>'arrow-down','ajuste'=>'sync'][$mov->tipo];
                $fecha = \Carbon\Carbon::parse($mov->created_at);
            @endphp
            <tr>
                <td class="mov-fecha">
                    <span class="mf-dia">{{ $fecha->format('d/m/Y') }}</span>
                    @if($fecha->isToday())<span class="mov-hoy-tag">HOY</span>@endif
                    <span class="mf-hora">{{ $fecha->format('H:i') }}</span>
                </td>
                <td class="row-title">
                    <div class="mov-materia">
                        <div class="mov-materia-icon"><i class="fas fa-wheat-awn"></i></div>
                        {{ $mov->materia->nombre }}
                    </div>
                </td>
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
                <td>
                    @if($mov->observacion)
                        <span class="mov-obs" title="{{ $mov->observacion }}">{{ $mov->observacion }}</span>
                    @else
                        <span class="mov-obs-empty">Sin observación</span>
                    @endif
                </td>
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
