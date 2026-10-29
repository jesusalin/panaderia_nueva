@extends('layouts.app')
@section('title', $producto->nombre)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
    <li class="breadcrumb-item active">{{ $producto->nombre }}</li>
@endsection

@push('styles')
<style>
    .pf-head { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); overflow: hidden; display: flex; flex-wrap: wrap; margin-bottom: 1.2rem; }
    .pf-img { width: 220px; min-height: 180px; background: linear-gradient(135deg, #1a1a2e, var(--cat-color)); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 3.2rem; flex-shrink: 0; }
    .pf-img img { width: 100%; height: 100%; object-fit: cover; }
    .pf-info { flex: 1; padding: 1.3rem 1.5rem; min-width: 260px; }
    .pf-cat { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: var(--cat-color); }
    .pf-info h2 { font-weight: 800; color: #1a1a2e; margin: .15rem 0 .2rem; font-size: 1.55rem; }
    .pf-info p { color: #8a8a9d; margin: 0 0 .9rem; font-size: .9rem; }
    .pf-cifras { display: flex; flex-wrap: wrap; gap: 1.6rem; }
    .pf-cifra small { display: block; font-size: .7rem; font-weight: 800; text-transform: uppercase; color: #8a8a9d; letter-spacing: .03em; }
    .pf-cifra b { font-size: 1.3rem; color: #1a1a2e; }
    .pf-cifra .verde { color: #1e8e5a; } .pf-cifra .rojo { color: #c0392b; } .pf-cifra .ambar { color: #a5680d; }
    .pf-acciones { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: 1rem; }

    .pf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.2rem; }
    @media (max-width: 992px) { .pf-grid { grid-template-columns: 1fr; } }
    .pf-box { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1.1rem 1.3rem; }
    .pf-box h5 { font-size: .95rem; font-weight: 800; color: #1a1a2e; margin-bottom: .9rem; }
    .pf-box table { width: 100%; font-size: .85rem; }
    .pf-box th { font-size: .7rem; text-transform: uppercase; color: #8a8a9d; font-weight: 800; padding: .3rem 0; border-bottom: 1px solid #f0ece8; }
    .pf-box td { padding: .4rem 0; border-bottom: 1px solid #f6f3f0; }
    .pf-box th + th, .pf-box td + td { padding-left: 1rem; }
    .pf-box tr:last-child td { border-bottom: none; }
    .pf-aviso { font-size: .8rem; border-radius: 8px; padding: .5rem .7rem; margin-top: .7rem; background: #fff8ec; color: #8a6215; }

    .pf-barras { display: flex; align-items: flex-end; gap: 4px; height: 110px; padding-top: .5rem; }
    .pf-barras .b { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .pf-barras .b span { width: 100%; background: var(--cat-color); border-radius: 4px 4px 0 0; min-height: 2px; opacity: .85; }
    .pf-barras .b em { font-style: normal; font-size: .62rem; color: #adb5bd; margin-top: .2rem; }
    .pf-barras .b:hover span { opacity: 1; }
    .pf-mov-tipo { font-weight: 800; }
    .pf-mov-tipo.entrada { color: #1e8e5a; } .pf-mov-tipo.salida { color: #c0392b; }
    body.dark-mode .pf-head, body.dark-mode .pf-box { background: #1f1f33; }
    body.dark-mode .pf-info h2, body.dark-mode .pf-cifra b, body.dark-mode .pf-box h5 { color: #f0f0f7; }
    body.dark-mode .pf-box th, body.dark-mode .pf-box td { border-color: #33334d; }
</style>
@endpush

@section('content')
@php
    $color = $producto->categoria->color_final ?? '#b5451b';
    $nivel = $producto->estaAgotado() ? 'rojo' : ($producto->tieneStockBajo() ? 'ambar' : 'verde');
    $maxDia = max(1, $dias->max('cantidad'));
    $motivos = ['produccion' => 'Producción', 'venta' => 'Venta', 'ajuste_manual' => 'Ajuste', 'devolucion' => 'Devolución'];
@endphp

<div class="pf-head" style="--cat-color: {{ $color }}">
    <div class="pf-img">
        @if($producto->imagen)
            <img src="{{ asset('storage/' . $producto->imagen) }}" alt="{{ $producto->nombre }}">
        @else
            <i class="fas {{ $producto->categoria->icono_final ?? 'fa-bread-slice' }}"></i>
        @endif
    </div>
    <div class="pf-info">
        <div class="pf-cat">{{ $producto->categoria->nombre ?? 'Sin categoría' }}
            @if($producto->estado !== 'activo')<span class="badge-soft badge-soft-secondary ml-1">Inactivo</span>@endif
        </div>
        <h2>{{ $producto->nombre }}</h2>
        <p>{{ $producto->descripcion ?: 'Sin descripción' }}</p>
        <div class="pf-cifras">
            <div class="pf-cifra"><small>Precio</small><b>S/ {{ number_format($producto->precio_venta, 2) }}</b></div>
            <div class="pf-cifra"><small>Costo</small><b>{{ $producto->costo_produccion > 0 ? 'S/ ' . number_format($producto->costo_produccion, 2) : '—' }}</b></div>
            <div class="pf-cifra"><small>Ganancia por unidad</small>
                @if($producto->ganancia !== null)
                    <b class="{{ $producto->ganancia < 0 ? 'rojo' : 'verde' }}">S/ {{ number_format($producto->ganancia, 2) }} <small style="display:inline">({{ $producto->margen }}%)</small></b>
                @else <b>—</b> @endif
            </div>
            <div class="pf-cifra"><small>Stock (mín. {{ $producto->stock_minimo }})</small><b class="{{ $nivel }}">{{ $producto->stock_actual }} uds</b></div>
            @if($resumen['diasCobertura'] !== null)
                <div class="pf-cifra"><small>Alcanza para</small><b class="{{ $resumen['diasCobertura'] < 1 ? 'rojo' : '' }}">{{ $resumen['diasCobertura'] < 1 ? 'menos de 1 día' : $resumen['diasCobertura'] . ' día(s)' }}</b></div>
            @endif
        </div>
        <div class="pf-acciones">
            <a href="{{ route('productos.edit', $producto) }}" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i>Editar</a>
            <a href="{{ route('productos.ajuste', $producto) }}" class="btn btn-info btn-sm"><i class="fas fa-balance-scale mr-1"></i>Ajustar stock</a>
            @if(auth()->user()->hasPermiso('produccion.produccion'))
                <a href="{{ route('produccion.create', ['producto' => $producto->id]) }}" class="btn btn-light btn-sm"><i class="fas fa-industry mr-1"></i>Producir</a>
            @endif
            @if(auth()->user()->hasPermiso('reportes.kardex'))
                <a href="{{ route('reportes.kardex', ['tipo_item' => 'producto', 'id_item' => $producto->id]) }}" class="btn btn-light btn-sm"><i class="fas fa-file-invoice mr-1"></i>Kardex completo</a>
            @endif
            <a href="{{ route('productos.index') }}" class="btn btn-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
        </div>
    </div>
</div>

<div class="pf-grid" style="--cat-color: {{ $color }}">
    <div class="pf-box">
        <h5><i class="fas fa-chart-bar mr-1 text-muted"></i>Ventas de los últimos 14 días</h5>
        <div class="pf-barras">
            @foreach($dias as $d)
                <div class="b" title="{{ $d['fecha']->locale('es')->translatedFormat('D d/m') }}: {{ $d['cantidad'] }} uds">
                    <span style="height: {{ round($d['cantidad'] / $maxDia * 90) }}%"></span>
                    <em>{{ $d['fecha']->format('d') }}</em>
                </div>
            @endforeach
        </div>
        <table class="mt-3">
            <tr><td>Vendidos (30 días)</td><td class="text-right"><strong>{{ $resumen['vendidos30'] }} uds</strong></td></tr>
            <tr><td>Ingresos (30 días, sin IGV)</td><td class="text-right"><strong>S/ {{ number_format($resumen['ingresos30'], 2) }}</strong></td></tr>
            <tr><td>Promedio por día</td><td class="text-right">{{ $resumen['promedioDia'] }} uds</td></tr>
            <tr><td>Producido (30 días)</td><td class="text-right">{{ $resumen['producido30'] }} uds</td></tr>
        </table>
    </div>

    <div class="pf-box">
        <h5><i class="fas fa-clipboard-list mr-1 text-muted"></i>Receta</h5>
        @if($producto->receta && $producto->receta->detalles->count())
            <p class="text-muted small mb-2">Rinde {{ $producto->receta->rendimiento }} unidad(es) por tanda.</p>
            <table>
                <thead><tr><th>Insumo</th><th class="text-right">Cantidad</th><th class="text-right">Costo</th></tr></thead>
                <tbody>
                @foreach($producto->receta->detalles as $d)
                    <tr>
                        <td>{{ $d->materia->nombre ?? '—' }}</td>
                        <td class="text-right">{{ \App\Support\Cantidad::fmt($d->cantidad) }} {{ $d->materia->unidad->abreviatura ?? '' }}</td>
                        <td class="text-right">S/ {{ number_format($d->cantidad * ($d->materia->costo_unitario ?? 0), 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if($costoReceta !== null)
                <p class="mt-2 mb-0 small">Costo por unidad según la receta: <strong>S/ {{ number_format($costoReceta, 2) }}</strong></p>
                @if(abs($costoReceta - (float) $producto->costo_produccion) >= 0.05)
                    <div class="pf-aviso"><i class="fas fa-info-circle mr-1"></i>El costo registrado (S/ {{ number_format($producto->costo_produccion, 2) }}) no coincide con la receta.
                        <a href="{{ route('productos.edit', $producto) }}">Actualizarlo</a></div>
                @endif
            @endif
        @else
            <p class="text-muted mb-2">Este producto todavía no tiene receta.</p>
            @if(auth()->user()->hasPermiso('produccion.recetas'))
                <a href="{{ route('produccion.recetas') }}" class="btn btn-light btn-sm"><i class="fas fa-plus mr-1"></i>Crear receta</a>
            @endif
        @endif
    </div>

    <div class="pf-box" style="grid-column: 1 / -1;">
        <h5><i class="fas fa-exchange-alt mr-1 text-muted"></i>Últimos movimientos</h5>
        @if($movimientos->count())
        <div class="table-responsive">
        <table>
            <thead><tr><th>Fecha</th><th>Motivo</th><th class="text-right">Cantidad</th><th class="text-right">Stock</th><th>Detalle</th><th>Usuario</th></tr></thead>
            <tbody>
            @foreach($movimientos as $m)
                <tr>
                    <td>{{ $m->created_at?->format('d/m H:i') }}</td>
                    <td>{{ $motivos[$m->motivo] ?? ucfirst($m->motivo) }}</td>
                    <td class="text-right pf-mov-tipo {{ $m->tipo }}">{{ $m->tipo === 'entrada' ? '+' : '−' }}{{ $m->cantidad }}</td>
                    <td class="text-right">{{ $m->stock_antes }} → <strong>{{ $m->stock_despues }}</strong></td>
                    <td class="text-muted">{{ \Illuminate\Support\Str::limit($m->observacion, 50) }}</td>
                    <td class="text-muted">{{ $m->usuario->nombre ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @else
            <p class="text-muted mb-0">Todavía no tiene movimientos.</p>
        @endif
    </div>
</div>
@endsection
