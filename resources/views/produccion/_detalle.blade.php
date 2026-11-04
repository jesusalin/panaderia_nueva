{{-- Detalle de una producción (se usa en el modal "Ver detalle" y en la página show) --}}
@php
    $p      = $produccion->producto;
    $color  = $p->categoria->color_final ?? '#b5451b';
    $icono  = $p->categoria->icono_final ?? 'fa-bread-slice';
    $ganancia = $valorVenta - $costo;
    $margen = $valorVenta > 0 && $costo > 0 ? round($ganancia / $valorVenta * 100, 1) : null;
    $puedeEliminar = $p->stock_actual >= $produccion->cantidad;
    $maxConsumo = max(0.0001, $consumos->max(fn ($m) => $m->cantidad * ($m->materia->costo_unitario ?? 0)) ?: 0.0001);
@endphp
<style>
    .pdx { --cat: {{ $color }}; }
    .pdx-hero { display: flex; gap: 1.2rem; align-items: center; padding: 1.3rem 1.4rem; border-radius: 14px;
        background: linear-gradient(135deg, #1a1a2e 0%, #23233d 55%, var(--cat) 140%); color: #fff; flex-wrap: wrap; }
    .pdx-foto { width: 74px; height: 74px; border-radius: 16px; background: rgba(255,255,255,.12); border: 2px solid rgba(255,255,255,.25);
        display: flex; align-items: center; justify-content: center; font-size: 1.9rem; overflow: hidden; flex-shrink: 0; }
    .pdx-foto img { width: 100%; height: 100%; object-fit: cover; }
    .pdx-hero .ph-num { font-size: .75rem; letter-spacing: .06em; text-transform: uppercase; opacity: .7; font-weight: 700; }
    .pdx-hero h4 { margin: .1rem 0 .25rem; font-weight: 800; }
    .pdx-hero .ph-meta { font-size: .84rem; opacity: .85; display: flex; flex-wrap: wrap; gap: .3rem 1rem; }
    .pdx-hero .ph-meta i { opacity: .7; margin-right: .25rem; }
    .pdx-hero .ph-cant { margin-left: auto; text-align: right; }
    .pdx-hero .ph-cant b { display: block; font-size: 2.2rem; line-height: 1; font-weight: 800; }
    .pdx-hero .ph-cant span { font-size: .78rem; opacity: .8; }

    .pdx-kpis { display: grid; grid-template-columns: repeat(4, 1fr); gap: .8rem; margin: 1rem 0; }
    .pdx-kpi { background: #fff; border: 1px solid #f0ebe6; border-radius: 12px; padding: .8rem .95rem; }
    .pdx-kpi small { display: block; font-size: .68rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #8a8a9d; }
    .pdx-kpi b { font-size: 1.2rem; color: #1a1a2e; }
    .pdx-kpi .sub { display: block; font-size: .74rem; color: #adb5bd; margin-top: .1rem; }
    .pdx-kpi.verde b { color: #1e8e5a; } .pdx-kpi.rojo b { color: #c0392b; }
    body.dark-mode .pdx-kpi { background: #24243b; border-color: #33334d; }
    body.dark-mode .pdx-kpi b { color: #f0f0f7; }
    @media (max-width: 767px) { .pdx-kpis { grid-template-columns: repeat(2, 1fr); } }

    .pdx-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 1rem; }
    @media (max-width: 991px) { .pdx-grid { grid-template-columns: 1fr; } }
    .pdx-box { background: #fff; border: 1px solid #f0ebe6; border-radius: 12px; padding: 1rem 1.1rem; }
    .pdx-box h6 { font-weight: 800; color: #1a1a2e; font-size: .9rem; margin-bottom: .8rem; }
    .pdx-box h6 i { color: var(--cat); margin-right: .35rem; }
    body.dark-mode .pdx-box { background: #24243b; border-color: #33334d; }
    body.dark-mode .pdx-box h6 { color: #f0f0f7; }

    .pdx-ing { display: grid; grid-template-columns: 1fr auto auto; gap: .2rem .8rem; align-items: center; padding: .5rem 0; border-bottom: 1px dashed #eee; font-size: .85rem; }
    .pdx-ing:last-child { border-bottom: none; }
    body.dark-mode .pdx-ing { border-bottom-color: #33334d; }
    .pdx-ing .pi-nombre { font-weight: 700; color: #343a40; }
    body.dark-mode .pdx-ing .pi-nombre { color: #e0e0ea; }
    .pdx-ing .pi-cant { font-weight: 800; color: #c0392b; white-space: nowrap; }
    .pdx-ing .pi-costo { color: #8a8a9d; white-space: nowrap; min-width: 64px; text-align: right; }
    .pdx-ing .pi-barra { grid-column: 1 / -1; height: 4px; border-radius: 4px; background: #f1ece7; overflow: hidden; }
    .pdx-ing .pi-barra span { display: block; height: 100%; background: var(--cat); opacity: .7; }
    .pdx-ing .pi-stock { grid-column: 1 / -1; font-size: .72rem; color: #adb5bd; margin-top: -.1rem; }
    .pdx-total { display: flex; justify-content: space-between; padding-top: .7rem; margin-top: .3rem; border-top: 2px solid #f1ece7; font-weight: 800; }

    .pdx-linea { list-style: none; margin: 0; padding: 0; }
    .pdx-linea li { position: relative; padding: 0 0 .9rem 1.4rem; font-size: .84rem; color: #6c757d; }
    .pdx-linea li::before { content: ''; position: absolute; left: .3rem; top: .35rem; width: 9px; height: 9px; border-radius: 50%; background: var(--cat); }
    .pdx-linea li::after { content: ''; position: absolute; left: .62rem; top: 1rem; bottom: 0; width: 1px; background: #e9e2dc; }
    .pdx-linea li:last-child::after { display: none; }
    .pdx-linea b { color: #1a1a2e; }
    body.dark-mode .pdx-linea b { color: #f0f0f7; }
    .pdx-nota { background: rgba(185,119,14,.07); border-left: 3px solid #b9770e; border-radius: 8px; padding: .7rem .9rem; font-size: .84rem; margin-top: .4rem; }
    .pdx-acciones { display: flex; flex-wrap: wrap; gap: .5rem; justify-content: flex-end; margin-top: 1rem; }
</style>

<div class="pdx">
    <div class="pdx-hero">
        <div class="pdx-foto">
            @if($p->imagen)<img src="{{ asset('storage/' . $p->imagen) }}" alt="{{ $p->nombre }}">@else<i class="fas {{ $icono }}"></i>@endif
        </div>
        <div>
            <div class="ph-num">Producción #{{ $produccion->id }} · {{ $p->categoria->nombre ?? 'Sin categoría' }}</div>
            <h4>{{ $p->nombre }}</h4>
            <div class="ph-meta">
                <span><i class="far fa-calendar"></i>{{ $produccion->fecha->locale('es')->translatedFormat('l d \d\e F Y') }}</span>
                <span><i class="far fa-clock"></i>registrada a las {{ $produccion->created_at?->format('H:i') }}</span>
                <span><i class="far fa-user"></i>{{ $produccion->usuario->nombre ?? '—' }}</span>
            </div>
        </div>
        <div class="ph-cant"><b>+{{ $produccion->cantidad }}</b><span>unidades al stock</span></div>
    </div>

    <div class="pdx-kpis">
        <div class="pdx-kpi"><small>Tandas</small><b>{{ $tandas !== null ? \App\Support\Cantidad::fmt($tandas, 2) : '—' }}</b>
            <span class="sub">{{ $p->receta ? 'de ' . $p->receta->rendimiento . ' und. cada una' : 'sin receta' }}</span></div>
        <div class="pdx-kpi"><small>Costo de insumos</small><b>S/ {{ number_format($costo, 2) }}</b>
            <span class="sub">S/ {{ number_format($produccion->cantidad ? $costo / $produccion->cantidad : 0, 2) }} por unidad</span></div>
        <div class="pdx-kpi"><small>Valor de venta</small><b>S/ {{ number_format($valorVenta, 2) }}</b>
            <span class="sub">a S/ {{ number_format($p->precio_venta, 2) }} c/u</span></div>
        <div class="pdx-kpi {{ $ganancia >= 0 ? 'verde' : 'rojo' }}"><small>Ganancia estimada</small><b>S/ {{ number_format($ganancia, 2) }}</b>
            <span class="sub">{{ $margen !== null ? $margen . '% del valor de venta' : 'si se vende todo' }}</span></div>
    </div>

    <div class="pdx-grid">
        <div class="pdx-box">
            <h6><i class="fas fa-flask"></i>Insumos descontados</h6>
            @forelse($consumos as $m)
                @php $c = $m->cantidad * ($m->materia->costo_unitario ?? 0); $u = $m->materia->unidad->abreviatura ?? ''; @endphp
                <div class="pdx-ing">
                    <span class="pi-nombre">{{ $m->materia->nombre ?? '—' }}</span>
                    <span class="pi-cant">−{{ \App\Support\Cantidad::conUnidad($m->cantidad, $u) }}</span>
                    <span class="pi-costo">S/ {{ number_format($c, 2) }}</span>
                    <span class="pi-barra"><span style="width: {{ round($c / $maxConsumo * 100) }}%"></span></span>
                    <span class="pi-stock">stock: {{ \App\Support\Cantidad::conUnidad($m->stock_antes, $u) }} → {{ \App\Support\Cantidad::conUnidad($m->stock_despues, $u) }}</span>
                </div>
            @empty
                <p class="text-muted mb-0">No hay movimientos de insumos registrados para esta producción.</p>
            @endforelse
            @if($consumos->count())
                <div class="pdx-total"><span>Total (a costo actual)</span><span>S/ {{ number_format($costo, 2) }}</span></div>
            @endif
        </div>

        <div class="pdx-box">
            <h6><i class="fas fa-stream"></i>Qué pasó</h6>
            <ul class="pdx-linea">
                <li>Se descontaron <b>{{ $consumos->count() }} insumo(s)</b> del almacén según la receta.</li>
                @if($entrada)
                    <li>El stock de {{ $p->nombre }} pasó de <b>{{ $entrada->stock_antes }}</b> a <b>{{ $entrada->stock_despues }}</b> unidades.</li>
                @endif
                <li>Hoy tiene <b>{{ $p->stock_actual }}</b> unidades en stock (mínimo {{ $p->stock_minimo }}).</li>
            </ul>
            @if($produccion->observacion)
                <div class="pdx-nota"><i class="fas fa-comment-alt mr-1"></i>{{ $produccion->observacion }}</div>
            @endif
        </div>
    </div>

    <div class="pdx-acciones">
        @if(auth()->user()->hasPermiso('catalogo.productos'))
            <a href="{{ route('productos.show', $p) }}" class="btn btn-light"><i class="fas fa-bread-slice mr-1"></i>Ver producto</a>
        @endif
        @if(auth()->user()->hasPermiso('produccion.produccion'))
            <a href="{{ route('produccion.create', ['producto' => $p->id, 'cantidad' => $produccion->cantidad]) }}" class="btn btn-light"><i class="fas fa-redo mr-1"></i>Repetir producción</a>
        @endif
        @if($puedeEliminar)
            <form action="{{ route('produccion.destroy', $produccion) }}" method="POST" class="js-confirm d-inline"
                data-confirm-title="¿Eliminar esta producción?"
                data-confirm="Se quitarán {{ $produccion->cantidad }} unidades de &quot;{{ $p->nombre }}&quot; del stock y se devolverán los insumos que se habían descontado. Esta acción NO se puede deshacer.">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger"><i class="fas fa-trash-alt mr-1"></i>Eliminar</button>
            </form>
        @else
            <button type="button" class="btn btn-outline-secondary is-locked js-blocked"
                data-blocked-title="No se puede eliminar esta producción"
                data-blocked-message="Ya se vendieron o movieron unidades de &quot;{{ $p->nombre }}&quot; desde que se registró. Eliminarla dejaría el stock en negativo.">
                <i class="fas fa-trash-alt mr-1"></i>Eliminar
            </button>
        @endif
    </div>
</div>
