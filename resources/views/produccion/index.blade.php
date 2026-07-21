@extends('layouts.app')
@section('title', 'Producción')
@section('breadcrumb')
    <li class="breadcrumb-item active">Producción</li>
@endsection

@push('styles')
<style>
    .prod-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .prod-stat {
        background: #fff; border-radius: 12px; padding: 1rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .8rem;
    }
    body.dark-mode .prod-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .prod-stat .ps-icon {
        width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .prod-stat .ps-value { font-size: 1.3rem; font-weight: 800; color: #1a1a2e; line-height: 1.15; }
    body.dark-mode .prod-stat .ps-value { color: #f0f0f7; }
    .prod-stat .ps-value.ps-value-sm { font-size: 1rem; }
    .prod-stat .ps-label { font-size: .74rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .2rem; }
    .prod-stat.total    { border-left-color: #b5451b; }
    .prod-stat.total .ps-icon    { background: rgba(181,69,27,.12); color: #b5451b; }
    .prod-stat.unidades { border-left-color: #2ecc71; }
    .prod-stat.unidades .ps-icon { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .prod-stat.hoy      { border-left-color: #3498db; }
    .prod-stat.hoy .ps-icon      { background: rgba(52,152,219,.14); color: #2170a3; }
    .prod-stat.top       { border-left-color: #f39c12; }
    .prod-stat.top .ps-icon      { background: rgba(243,156,18,.14); color: #b9770e; }

    .prod-icon { overflow: hidden; }
    .prod-icon img { width: 100%; height: 100%; object-fit: cover; }
    .prod-nombre { line-height: 1.25; }
    .prod-cat { display: block; font-size: .72rem; color: #adb5bd; font-weight: 400; }

    .prod-fecha .pf-dia { font-weight: 700; color: #1a1a2e; }
    body.dark-mode .prod-fecha .pf-dia { color: #f0f0f7; }
    .prod-hoy-tag { font-size: .65rem; font-weight: 800; color: #2170a3; background: rgba(52,152,219,.14); padding: .05rem .4rem; border-radius: 8px; margin-left: .3rem; vertical-align: middle; }
    body.dark-mode .prod-hoy-tag { background: rgba(52,152,219,.2); color: #7ec3f5; }

    .prod-obs {
        display: inline-block; max-width: 220px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        font-size: .82rem; color: #8a8a9d; vertical-align: middle;
    }
    body.dark-mode .prod-obs { color: #9a9ac0; }
    .prod-obs-empty { color: #ced4da; font-style: italic; }

    @media (max-width: 768px) { .prod-stats { grid-template-columns: repeat(2, 1fr); } }
</style>
@endpush

@section('content')

@include('partials.tabs-productos')

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

<div class="prod-stats">
    <div class="prod-stat total">
        <div class="ps-icon"><i class="fas fa-industry"></i></div>
        <div><div class="ps-value">{{ $stats['total'] }}</div><div class="ps-label">Producciones</div></div>
    </div>
    <div class="prod-stat unidades">
        <div class="ps-icon"><i class="fas fa-box-open"></i></div>
        <div><div class="ps-value">{{ number_format($stats['unidades']) }}</div><div class="ps-label">Unidades producidas</div></div>
    </div>
    <div class="prod-stat hoy">
        <div class="ps-icon"><i class="fas fa-calendar-day"></i></div>
        <div><div class="ps-value">{{ $stats['hoy'] }}</div><div class="ps-label">Hoy</div></div>
    </div>
    <div class="prod-stat top">
        <div class="ps-icon"><i class="fas fa-trophy"></i></div>
        <div><div class="ps-value ps-value-sm">{{ $stats['top']->producto->nombre ?? '—' }}</div><div class="ps-label">Más producido</div></div>
    </div>
</div>

<form method="GET" action="{{ route('produccion.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <label class="fb-label mb-0">Producto</label>
    <select name="id_producto" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos los productos</option>
        @foreach($productos as $p)
            <option value="{{ $p->id }}" {{ request('id_producto') == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>
        @endforeach
    </select>
    <label class="fb-label mb-0">Desde</label>
    <input type="date" name="fecha_desde" class="form-control" style="width:auto;" value="{{ request('fecha_desde') }}">
    <label class="fb-label mb-0">Hasta</label>
    <input type="date" name="fecha_hasta" class="form-control" style="width:auto;" value="{{ request('fecha_hasta') }}">
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Aplicar</button>
    @if(request()->anyFilled(['id_producto','fecha_desde','fecha_hasta']))
        <a href="{{ route('produccion.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

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
            @php
                $catLower = mb_strtolower($p->producto->categoria->nombre ?? '');
                $iconoProd = 'fa-bread-slice';
                if (str_contains($catLower, 'pastel') || str_contains($catLower, 'torta')) $iconoProd = 'fa-birthday-cake';
                elseif (str_contains($catLower, 'galleta'))  $iconoProd = 'fa-cookie';
                elseif (str_contains($catLower, 'empanada')) $iconoProd = 'fa-cheese';
                elseif (str_contains($catLower, 'bebida') || str_contains($catLower, 'café')) $iconoProd = 'fa-mug-hot';
            @endphp
            <tr>
                <td class="text-muted small">#{{ $p->id }}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="row-icon prod-icon">
                            @if($p->producto->imagen)
                                <img src="{{ asset('storage/'.$p->producto->imagen) }}" alt="{{ $p->producto->nombre }}">
                            @else
                                <i class="fas {{ $iconoProd }}"></i>
                            @endif
                        </div>
                        <div class="ml-2 prod-nombre">
                            <span class="row-title">{{ $p->producto->nombre }}</span>
                            @if($p->producto->categoria)
                                <span class="prod-cat">{{ $p->producto->categoria->nombre }}</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="text-center"><span class="badge-soft badge-soft-success">+{{ $p->cantidad }} und.</span></td>
                <td class="prod-fecha">
                    <span class="pf-dia">{{ $p->fecha->format('d/m/Y') }}</span>
                    @if($p->fecha->isToday())<span class="prod-hoy-tag">HOY</span>@endif
                </td>
                <td>{{ $p->usuario->nombre ?? '—' }}</td>
                <td>
                    @if($p->observacion)
                        <span class="prod-obs" title="{{ $p->observacion }}">{{ $p->observacion }}</span>
                    @else
                        <span class="prod-obs-empty">Sin observación</span>
                    @endif
                </td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('produccion.show', $p) }}" class="btn btn-icon btn-info" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($p->producto->stock_actual >= $p->cantidad)
                            <form action="{{ route('produccion.destroy', $p) }}" method="POST" class="js-confirm"
                                data-confirm-title="¿Eliminar esta producción?"
                                data-confirm="Se quitarán {{ $p->cantidad }} unidades de &quot;{{ $p->producto->nombre }}&quot; del stock y se devolverán los insumos que se habían descontado. Esta acción NO se puede deshacer.">
                                @csrf @method('DELETE')
                                <button class="btn btn-icon btn-danger" title="Eliminar (registrado por error)"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        @else
                            <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                                title="No se puede eliminar"
                                data-blocked-title="No se puede eliminar esta producción"
                                data-blocked-message="Ya se vendieron o movieron unidades de &quot;{{ $p->producto->nombre }}&quot; desde que se registró. Eliminarla dejaría el stock en negativo.">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        @endif
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
    <p>No hay producciones registradas con este filtro</p>
    <a href="{{ route('produccion.create') }}" class="btn btn-warning"><i class="fas fa-plus mr-1"></i>Registrar la primera</a>
</div>
@endif

@endsection
