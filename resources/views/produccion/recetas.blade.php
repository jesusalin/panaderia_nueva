@extends('layouts.app')
@section('title', 'Recetas')
@section('breadcrumb')
    @if(auth()->user()->hasPermiso('produccion.produccion'))
    <li class="breadcrumb-item"><a href="{{ route('produccion.index') }}">Producción</a></li>
    @endif
    <li class="breadcrumb-item active">Recetas</li>
@endsection

@push('styles')
<style>
    .rec-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    .rec-stat {
        background: #fff; border-radius: 12px; padding: 1rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border-left: 4px solid #dee2e6; display: flex; align-items: center; gap: .8rem;
    }
    body.dark-mode .rec-stat { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .rec-stat .rst-icon {
        width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
    }
    .rec-stat .rst-value { font-size: 1.35rem; font-weight: 800; color: #1a1a2e; line-height: 1; }
    body.dark-mode .rec-stat .rst-value { color: #f0f0f7; }
    .rec-stat .rst-label { font-size: .74rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-top: .2rem; }
    .rec-stat.total    { border-left-color: #b5451b; }
    .rec-stat.total .rst-icon    { background: rgba(181,69,27,.12); color: #b5451b; }
    .rec-stat.con      { border-left-color: #2ecc71; }
    .rec-stat.con .rst-icon      { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .rec-stat.sin      { border-left-color: #f39c12; }
    .rec-stat.sin .rst-icon      { background: rgba(243,156,18,.14); color: #b9770e; }
    .rec-stat.aviso    { border-left-color: #e74c3c; }
    .rec-stat.aviso .rst-icon    { background: rgba(231,76,60,.12); color: #c0392b; }
    a.rec-stat { text-decoration: none; color: inherit; }
    a.rec-stat:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }

    .rec-card-media > i { color: var(--cat-color, #b5451b) !important; }
    .rec-costo { margin: 0 1.1rem .6rem; padding: .55rem .75rem; background: #f7f5f3; border-radius: 9px; font-size: .78rem; color: #6c757d;
        display: flex; flex-wrap: wrap; gap: .2rem .9rem; align-items: center; }
    .rec-costo b { color: #1a1a2e; }
    .rec-costo .rc-margen { margin-left: auto; font-weight: 800; padding: .1rem .5rem; border-radius: 20px; background: rgba(46,204,113,.14); color: #1e8e5a; }
    .rec-costo .rc-margen.baja { background: rgba(243,156,18,.16); color: #a5680d; }
    .rec-costo .rc-margen.perdida { background: rgba(231,76,60,.14); color: #c0392b; }
    body.dark-mode .rec-costo { background: #24243b; color: #b0b0cc; }
    body.dark-mode .rec-costo b { color: #f0f0f7; }
    .rec-avisos { margin: 0 1.1rem .7rem; padding: .45rem .7rem; border-radius: 9px; background: rgba(231,76,60,.08); color: #b3261e; font-size: .76rem; }
    .rec-avisos i { margin-right: .3rem; }


    /* Tarjeta de receta (reusa .item-card pero con el ícono de producto en vez de foto grande) */
    .rec-card-media {
        height: 88px; background: #f7f5f3; display: flex; align-items: center; justify-content: center;
        position: relative; overflow: hidden;
    }
    body.dark-mode .rec-card-media { background: #24243b; }
    .rec-card-media img { width: 100%; height: 100%; object-fit: cover; }
    .rec-card-media > i { font-size: 1.8rem; color: #b5451b; }
    body.dark-mode .rec-card-media > i { color: #ff9d6e; }
    .rec-card-media .ic-badge { position: absolute; top: .5rem; right: .5rem; }

    .rec-ing-list { padding: .3rem 1.1rem .8rem; }
    .rec-ing-row { display: flex; justify-content: space-between; align-items: center; padding: .3rem 0; font-size: .82rem; border-bottom: 1px dashed #eee; }
    .rec-ing-row:last-child { border-bottom: none; }
    body.dark-mode .rec-ing-row { border-bottom-color: #33334d; }
    .rec-ing-row .ri-nombre { color: #495057; }
    body.dark-mode .rec-ing-row .ri-nombre { color: #c8c8d4; }
    .rec-ing-row .ri-cant { font-weight: 700; color: #1a1a2e; }
    body.dark-mode .rec-ing-row .ri-cant { color: #f0f0f7; }

    .rec-rendimiento { padding: .6rem 1.1rem; border-top: 1px solid #f2f2f2; font-size: .78rem; color: #8a8a9d; }
    body.dark-mode .rec-rendimiento { border-top-color: #33334d; color: #9a9ac0; }
    .rec-rendimiento strong { color: #1a1a2e; }
    body.dark-mode .rec-rendimiento strong { color: #f0f0f7; }

    .rec-sin-receta { text-align: center; padding: 1.6rem 1rem; color: #adb5bd; font-size: .85rem; }
    .rec-sin-receta i { font-size: 1.6rem; display: block; margin-bottom: .5rem; opacity: .5; }


    @media (max-width: 992px) { .rec-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .rec-stats { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')

@include('partials.tabs-productos')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-book mr-2 text-warning"></i>Recetas de Producción</h2>
        <p>Define los insumos y el rendimiento de cada producto para poder producirlo</p>
    </div>
</div>

<div class="rec-stats">
    <div class="rec-stat total">
        <div class="rst-icon"><i class="fas fa-bread-slice"></i></div>
        <div><div class="rst-value">{{ $stats['total'] }}</div><div class="rst-label">Productos activos</div></div>
    </div>
    <div class="rec-stat con">
        <div class="rst-icon"><i class="fas fa-check-circle"></i></div>
        <div><div class="rst-value">{{ $stats['con_receta'] }}</div><div class="rst-label">Con receta</div></div>
    </div>
    <a href="{{ route('produccion.recetas', ['estado' => 'sin']) }}" class="rec-stat sin">
        <div class="rst-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div><div class="rst-value">{{ $stats['sin_receta'] }}</div><div class="rst-label">Sin receta</div></div>
    </a>
    <a href="{{ route('produccion.recetas', ['estado' => 'aviso']) }}" class="rec-stat aviso">
        <div class="rst-icon"><i class="fas fa-exclamation-circle"></i></div>
        <div><div class="rst-value">{{ $stats['con_aviso'] }}</div><div class="rst-label">Con avisos</div></div>
    </a>
</div>

<form method="GET" action="{{ route('produccion.recetas') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" data-sugerencias="productos" autocomplete="off" class="form-control" placeholder="Buscar producto..." value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Mostrar</label>
    <select name="estado" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="con"   {{ request('estado') === 'con'   ? 'selected' : '' }}>Con receta</option>
        <option value="sin"   {{ request('estado') === 'sin'   ? 'selected' : '' }}>Sin receta</option>
        <option value="aviso" {{ request('estado') === 'aviso' ? 'selected' : '' }}>Con avisos</option>
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('estado'))
        <a href="{{ route('produccion.recetas') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

<div class="card-grid">
    @forelse($productos as $producto)
    @php $iconoP = $producto->categoria->icono_final ?? 'fa-bread-slice'; @endphp
    <div class="item-card" style="--cat-color: {{ $producto->categoria->color_final ?? '#b5451b' }}">
        <div class="rec-card-media">
            @if($producto->imagen)
                <img src="{{ asset('storage/'.$producto->imagen) }}" alt="{{ $producto->nombre }}">
            @else
                <i class="fas {{ $iconoP }}"></i>
            @endif
            <span class="ic-badge">
                @if($producto->receta && $producto->avisos_receta)
                    <span class="badge-soft badge-soft-danger"><i class="fas fa-exclamation-circle mr-1"></i>Revisar</span>
                @elseif($producto->receta)
                    <span class="badge-soft badge-soft-success"><i class="fas fa-check mr-1"></i>Receta OK</span>
                @else
                    <span class="badge-soft badge-soft-secondary">Sin receta</span>
                @endif
            </span>
        </div>

        <div class="item-card-body" style="padding-bottom:0;">
            <div class="item-card-cat">{{ $producto->categoria->nombre ?? 'Sin categoría' }}</div>
            <h3 class="item-card-title" style="font-size:.95rem;">{{ $producto->nombre }}</h3>
        </div>

        @if($producto->receta)
            <div class="rec-ing-list">
                @foreach($producto->receta->detalles as $d)
                <div class="rec-ing-row">
                    <span class="ri-nombre">{{ $d->materia->nombre }}</span>
                    <span class="ri-cant">{{ \App\Support\Cantidad::conUnidad($d->cantidad, $d->materia->unidad->abreviatura) }}</span>
                </div>
                @endforeach
            </div>
            @if($producto->costo_receta !== null)
                <div class="rec-costo">
                    <span>Cuesta <b>S/ {{ number_format($producto->costo_receta, 2) }}</b> c/u</span>
                    <span>Se vende a <b>S/ {{ number_format($producto->precio_venta, 2) }}</b></span>
                    @if($producto->margen_receta !== null)
                        <span class="rc-margen {{ $producto->margen_receta < 0 ? 'perdida' : ($producto->margen_receta < 20 ? 'baja' : '') }}">
                            {{ $producto->margen_receta < 0 ? 'Pierdes' : 'Ganas' }} {{ abs($producto->margen_receta) }}%
                        </span>
                    @endif
                </div>
            @endif
            @if($producto->avisos_receta)
                <div class="rec-avisos"><i class="fas fa-exclamation-triangle"></i>{{ ucfirst(implode('; ', array_unique($producto->avisos_receta))) }}.</div>
            @endif
            <div class="rec-rendimiento">
                Rinde: <strong>{{ $producto->receta->rendimiento }} unidades</strong>
                @if($producto->receta->descripcion)
                    — {{ $producto->receta->descripcion }}
                @endif
            </div>
        @else
            <div class="rec-sin-receta">
                <i class="fas fa-flask"></i>
                Sin receta registrada
            </div>
        @endif

        <div class="item-card-footer">
            <a href="{{ route('produccion.receta.editar', $producto) }}" class="btn btn-sm btn-outline-warning btn-block">
                <i class="fas fa-edit mr-1"></i>{{ $producto->receta ? 'Editar' : 'Crear' }} receta
            </a>
        </div>
    </div>
    @empty
    <div class="empty-state" style="grid-column: 1 / -1;">
        <i class="fas fa-book"></i>
        <p>{{ request('buscar') || request('estado') ? 'Ningún producto coincide con ese filtro.' : 'No hay productos activos.' }}</p>
        @if(auth()->user()->hasPermiso('catalogo.productos'))
        <a href="{{ route('productos.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear producto</a>
        @endif
    </div>
    @endforelse
</div>

@endsection
