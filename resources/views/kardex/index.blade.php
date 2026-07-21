@extends('layouts.app')
@section('title', 'Movimientos de Productos')
@section('breadcrumb') <li class="breadcrumb-item active">Movimientos de Productos</li> @endsection

@push('styles')
<style>
    .kdx-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .kdx-stat {
        background: #fff; border-radius: 12px; padding: 1rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .8rem;
    }
    body.dark-mode .kdx-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .kdx-stat .ks-icon {
        width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .kdx-stat .ks-value { font-size: 1.35rem; font-weight: 800; color: #1a1a2e; line-height: 1; }
    body.dark-mode .kdx-stat .ks-value { color: #f0f0f7; }
    .kdx-stat .ks-label { font-size: .74rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .2rem; }
    .kdx-stat.entradas { border-left-color: #2ecc71; }
    .kdx-stat.entradas .ks-icon { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .kdx-stat.salidas  { border-left-color: #e74c3c; }
    .kdx-stat.salidas .ks-icon { background: rgba(231,76,60,.12); color: #c0392b; }
    .kdx-stat.hoy      { border-left-color: #3498db; }
    .kdx-stat.hoy .ks-icon { background: rgba(52,152,219,.14); color: #2170a3; }
    .kdx-stat.ajustes  { border-left-color: #f39c12; }
    .kdx-stat.ajustes .ks-icon { background: rgba(243,156,18,.14); color: #b9770e; }

    .table-modern .kdx-producto { display: flex; align-items: center; gap: .6rem; }
    .table-modern .kdx-producto-icon {
        width: 32px; height: 32px; border-radius: 8px; background: #f7f5f3; color: #b5451b;
        display: flex; align-items: center; justify-content: center; font-size: .82rem; flex-shrink: 0;
        overflow: hidden;
    }
    .table-modern .kdx-producto-icon img { width: 100%; height: 100%; object-fit: cover; }
    body.dark-mode .table-modern .kdx-producto-icon { background: #24243b; color: #ff9d6e; }
    .kdx-producto-nombre { line-height: 1.25; }
    .kdx-producto-cat { display: block; font-size: .72rem; color: #adb5bd; font-weight: 400; }

    .kdx-motivo { display: inline-flex; align-items: center; gap: .4rem; font-size: .84rem; color: #495057; }
    body.dark-mode .kdx-motivo { color: #c8c8d4; }
    .kdx-motivo i { font-size: .78rem; color: #adb5bd; }

    .kdx-obs {
        display: inline-block; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        font-size: .82rem; color: #8a8a9d; vertical-align: middle;
    }
    body.dark-mode .kdx-obs { color: #9a9ac0; }
    .kdx-obs-empty { color: #ced4da; font-style: italic; }

    .kdx-fecha .kf-dia { font-weight: 700; color: #1a1a2e; }
    body.dark-mode .kdx-fecha .kf-dia { color: #f0f0f7; }
    .kdx-fecha .kf-hora { display: block; font-size: .74rem; color: #adb5bd; }
    .kdx-hoy-tag { font-size: .65rem; font-weight: 800; color: #2170a3; background: rgba(52,152,219,.14); padding: .05rem .4rem; border-radius: 8px; margin-left: .3rem; vertical-align: middle; }
    body.dark-mode .kdx-hoy-tag { background: rgba(52,152,219,.2); color: #7ec3f5; }

    @media (max-width: 768px) { .kdx-stats { grid-template-columns: repeat(2, 1fr); } }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-book mr-2 text-primary"></i>Movimientos de Productos</h2>
        <p>Historial de entradas y salidas de productos terminados (Kardex)</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('kardex.rotacion') }}" class="btn btn-success">
            <i class="fas fa-chart-bar mr-1"></i>Rotación de Stock
        </a>
    </div>
</div>

<div class="kdx-stats">
    <div class="kdx-stat entradas">
        <div class="ks-icon"><i class="fas fa-arrow-up"></i></div>
        <div><div class="ks-value">{{ $stats['entradas'] }}</div><div class="ks-label">Entradas</div></div>
    </div>
    <div class="kdx-stat salidas">
        <div class="ks-icon"><i class="fas fa-arrow-down"></i></div>
        <div><div class="ks-value">{{ $stats['salidas'] }}</div><div class="ks-label">Salidas</div></div>
    </div>
    <div class="kdx-stat hoy">
        <div class="ks-icon"><i class="fas fa-calendar-day"></i></div>
        <div><div class="ks-value">{{ $stats['hoy'] }}</div><div class="ks-label">Hoy</div></div>
    </div>
    <div class="kdx-stat ajustes">
        <div class="ks-icon"><i class="fas fa-sliders-h"></i></div>
        <div><div class="ks-value">{{ $stats['ajustes'] }}</div><div class="ks-label">Ajustes manuales</div></div>
    </div>
</div>

<form method="GET" action="{{ route('kardex.index') }}" class="filter-bar flex-wrap" style="gap:.6rem" onsubmit="TiempoOperacion.marcarInicio('verificacion_stock')">
    <label class="fb-label mb-0">Producto</label>
    <select name="id_producto" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos los productos</option>
        @foreach($productos as $p)
            <option value="{{ $p->id }}" {{ request('id_producto') == $p->id ? 'selected' : '' }}>
                {{ $p->nombre }}
            </option>
        @endforeach
    </select>
    <label class="fb-label mb-0">Tipo</label>
    <select name="tipo" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos los tipos</option>
        <option value="entrada" {{ request('tipo') == 'entrada' ? 'selected' : '' }}>Entradas</option>
        <option value="salida"  {{ request('tipo') == 'salida'  ? 'selected' : '' }}>Salidas</option>
    </select>
    <label class="fb-label mb-0">Desde</label>
    <input type="date" name="fecha_desde" class="form-control" style="width:auto;" value="{{ request('fecha_desde') }}">
    <label class="fb-label mb-0">Hasta</label>
    <input type="date" name="fecha_hasta" class="form-control" style="width:auto;" value="{{ request('fecha_hasta') }}">
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Aplicar</button>
    @if(request()->anyFilled(['id_producto','tipo','fecha_desde','fecha_hasta']))
        <a href="{{ route('kardex.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($movimientos->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Producto</th>
                <th class="text-center">Tipo</th>
                <th>Motivo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-center">Stock Antes</th>
                <th class="text-center">Stock Después</th>
                <th>Usuario</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $m)
            @php
                $motivoIcono = [
                    'produccion'    => 'industry',
                    'venta'         => 'cash-register',
                    'ajuste_manual' => 'sliders-h',
                    'devolucion'    => 'undo',
                ][$m->motivo] ?? 'circle';
                $fecha = \Carbon\Carbon::parse($m->created_at);

                $nombreCatLower = mb_strtolower($m->producto->categoria->nombre ?? '');
                $iconoProducto = 'fa-bread-slice';
                if (str_contains($nombreCatLower, 'pastel') || str_contains($nombreCatLower, 'torta')) $iconoProducto = 'fa-birthday-cake';
                elseif (str_contains($nombreCatLower, 'galleta'))  $iconoProducto = 'fa-cookie';
                elseif (str_contains($nombreCatLower, 'empanada')) $iconoProducto = 'fa-cheese';
                elseif (str_contains($nombreCatLower, 'bebida') || str_contains($nombreCatLower, 'café')) $iconoProducto = 'fa-mug-hot';
            @endphp
            <tr>
                <td class="kdx-fecha">
                    <span class="kf-dia">{{ $fecha->format('d/m/Y') }}</span>
                    @if($fecha->isToday())<span class="kdx-hoy-tag">HOY</span>@endif
                    <span class="kf-hora">{{ $fecha->format('H:i') }}</span>
                </td>
                <td class="row-title">
                    <div class="kdx-producto">
                        <div class="kdx-producto-icon">
                            @if($m->producto->imagen)
                                <img src="{{ asset('storage/'.$m->producto->imagen) }}" alt="{{ $m->producto->nombre }}">
                            @else
                                <i class="fas {{ $iconoProducto }}"></i>
                            @endif
                        </div>
                        <div class="kdx-producto-nombre">
                            {{ $m->producto->nombre }}
                            @if($m->producto->categoria)
                                <span class="kdx-producto-cat">{{ $m->producto->categoria->nombre }}</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="text-center">
                    @if($m->tipo === 'entrada')
                        <span class="badge-soft badge-soft-success"><i class="fas fa-arrow-up mr-1"></i>Entrada</span>
                    @else
                        <span class="badge-soft badge-soft-danger"><i class="fas fa-arrow-down mr-1"></i>Salida</span>
                    @endif
                </td>
                <td>
                    <span class="kdx-motivo"><i class="fas fa-{{ $motivoIcono }}"></i>{{ ucfirst(str_replace('_',' ',$m->motivo)) }}</span>
                </td>
                <td class="text-center font-weight-bold {{ $m->tipo === 'entrada' ? 'text-success' : 'text-danger' }}">
                    {{ $m->tipo === 'entrada' ? '+' : '-' }}{{ $m->cantidad }}
                </td>
                <td class="text-center">{{ $m->stock_antes }}</td>
                <td class="text-center font-weight-bold">{{ $m->stock_despues }}</td>
                <td>{{ $m->usuario->nombre ?? '—' }}</td>
                <td>
                    @if($m->observacion)
                        <span class="kdx-obs" title="{{ $m->observacion }}">{{ $m->observacion }}</span>
                    @else
                        <span class="kdx-obs-empty">Sin observación</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $movimientos->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-book"></i>
    <p>No hay movimientos registrados con este filtro</p>
</div>
@endif

@endsection

@push('scripts')
<script>
// Si venimos de aplicar un filtro (búsqueda de stock), registrar el tiempo que tomó
TiempoOperacion.registrarFin('verificacion_stock');
</script>
@endpush
