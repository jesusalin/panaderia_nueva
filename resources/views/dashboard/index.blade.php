@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.min.css">
<style>
    .dash-greeting { margin-bottom: 1.75rem; }
    .dash-greeting h2 { font-weight: 800; margin: 0; color: #1a1a2e; }
    .dash-greeting p { margin: .2rem 0 0; color: #8a8a9d; font-size: .9rem; text-transform: capitalize; }

    .section-heading {
        display: flex; align-items: center; gap: .6rem; margin: 2rem 0 1rem;
        font-weight: 800; font-size: .95rem; color: #1a1a2e;
    }
    .section-heading:first-of-type { margin-top: 0; }
    .section-heading .sh-icon {
        width: 30px; height: 30px; border-radius: 8px; background: #f4e9e3; color: #b5451b;
        display: flex; align-items: center; justify-content: center; font-size: .8rem;
    }
    .section-heading small { font-weight: 500; color: #adb5bd; margin-left: .3rem; }

    /* Hero: venta de hoy */
    .stat-hero {
        border-radius: 16px; padding: 1.75rem; color: #fff; position: relative; overflow: hidden;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 55%, #b5451b 100%);
        box-shadow: 0 10px 30px rgba(26,26,46,.25); height: 100%;
    }
    .stat-hero .label { font-size: .8rem; opacity: .75; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .stat-hero .value { font-size: 2.4rem; font-weight: 800; margin: .3rem 0; }
    .stat-hero .delta { display: inline-flex; align-items: center; gap: .3rem; font-size: .8rem; font-weight: 700; padding: .25rem .6rem; border-radius: 20px; }
    .stat-hero .delta.up { background: rgba(46,204,113,.25); color: #6ee7a5; }
    .stat-hero .delta.down { background: rgba(231,76,60,.25); color: #ff9b8f; }
    .stat-hero .delta.flat { background: rgba(255,255,255,.15); color: #cfcfe0; }
    .stat-hero .foot-link { display: block; margin-top: 1rem; color: #fff; opacity: .8; font-size: .8rem; text-decoration: none; }
    .stat-hero .foot-link:hover { opacity: 1; color: #fff; text-decoration: underline; }
    .stat-hero .bg-icon { position: absolute; right: -10px; bottom: -14px; font-size: 6rem; opacity: .08; }

    /* Stat card secundaria */
    .stat-card {
        background: #fff; border-radius: 14px; padding: 1.25rem 1.4rem; height: 100%;
        box-shadow: 0 2px 12px rgba(0,0,0,.05); border-left: 4px solid #dee2e6;
        display: flex; flex-direction: column; justify-content: space-between;
    }
    .stat-card.acc-success { border-left-color: #2ecc71; }
    .stat-card.acc-danger  { border-left-color: #e74c3c; }
    .stat-card.acc-warn    { border-left-color: #f39c12; }
    .stat-card.acc-info    { border-left-color: #3498db; }
    .stat-card .label { font-size: .78rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
    .stat-card .value { font-size: 1.55rem; font-weight: 800; color: #1a1a2e; margin: .25rem 0; }
    .stat-card .foot-link { font-size: .78rem; color: #b5451b; font-weight: 700; text-decoration: none; }
    .stat-card .foot-link:hover { text-decoration: underline; }

    /* Alertas */
    .alert-banner {
        border-radius: 14px; padding: 1.1rem 1.4rem; display: flex; align-items: center; gap: 1rem;
        margin-bottom: .5rem;
    }
    .alert-banner.ok    { background: #eafaf1; border: 1px solid #c8f0d8; color: #1e8e5a; }
    .alert-banner.warn  { background: #fff4e5; border: 1px solid #ffe1ae; color: #a5680d; }
    .alert-banner .ab-icon { font-size: 1.5rem; }
    .alert-banner .ab-text strong { display: block; font-size: .95rem; }
    .alert-banner .ab-text span { font-size: .82rem; opacity: .85; }
    .alert-banner .ab-action { margin-left: auto; white-space: nowrap; }

    /* KPI cards (indicadores de tesis) */
    .kpi-card {
        background: #fff; border-radius: 14px; padding: 1.3rem; height: 100%;
        box-shadow: 0 2px 12px rgba(0,0,0,.05); text-align: center;
    }
    .kpi-card .kpi-ring {
        width: 68px; height: 68px; border-radius: 50%; margin: 0 auto .7rem;
        display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.05rem;
        border: 5px solid #eee;
    }
    .kpi-card .kpi-ring.good { border-color: #2ecc71; color: #1e8e5a; }
    .kpi-card .kpi-ring.mid  { border-color: #f39c12; color: #a5680d; }
    .kpi-card .kpi-ring.bad  { border-color: #e74c3c; color: #b3261e; }
    .kpi-card .kpi-ring.neutral { border-color: #dee2e6; color: #6c757d; }
    .kpi-card .kpi-title { font-weight: 800; font-size: .85rem; color: #1a1a2e; margin-bottom: .25rem; }
    .kpi-card .kpi-desc { font-size: .76rem; color: #8a8a9d; line-height: 1.35; }

    /* ══════════ Modo oscuro (mismos tonos que el resto del sistema) ══════════ */
    body.dark-mode .dash-greeting h2 { color: #f0f0f7; }
    body.dark-mode .dash-greeting p { color: #9a9ac0; }

    body.dark-mode .section-heading { color: #f0f0f7; }
    body.dark-mode .section-heading .sh-icon { background: rgba(181,69,27,.22); color: #ff9d6e; }
    body.dark-mode .section-heading small { color: #6c6c8a; }

    body.dark-mode .stat-card { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    body.dark-mode .stat-card .label { color: #9a9ac0; }
    body.dark-mode .stat-card .value { color: #f0f0f7; }
    body.dark-mode .stat-card .foot-link { color: #ff9d6e; }

    body.dark-mode .kpi-card { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    body.dark-mode .kpi-card .kpi-title { color: #f0f0f7; }
    body.dark-mode .kpi-card .kpi-desc { color: #9a9ac0; }
    body.dark-mode .kpi-card .kpi-desc a { color: #ff9d6e; }
    body.dark-mode .kpi-ring { border-color: #33334d; }
    body.dark-mode .kpi-ring.good    { border-color: #2ecc71; color: #6ee7a5; }
    body.dark-mode .kpi-ring.mid     { border-color: #f39c12; color: #ffc673; }
    body.dark-mode .kpi-ring.bad     { border-color: #e74c3c; color: #ff9b8f; }
    body.dark-mode .kpi-ring.neutral { border-color: #33334d; color: #9a9ac0; }
</style>
@endpush

@section('content')

@php
    $u = auth()->user();
    $vePonderadoNegocio = $u->hasModulo('ventas') || $u->hasModulo('compras');
    $veAlertas   = $u->hasModulo('inventario') || $u->hasModulo('compras');
    $veIndicadores = $u->hasModulo('reportes') || $u->isAdmin();
    $totalAlertas = $productosStockBajo + $materiaStockBaja;
@endphp

<div class="dash-greeting">
    <h2>Hola, {{ $u->apodo ?? explode(' ', $u->nombre)[0] }} 👋</h2>
    <p>{{ \Carbon\Carbon::now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}</p>
</div>

{{-- ══════════ TU NEGOCIO HOY ══════════ --}}
@if($vePonderadoNegocio)
<div class="section-heading">
    <span class="sh-icon"><i class="fas fa-store"></i></span> Tu negocio hoy
</div>
<div class="row">
    @if($u->hasModulo('ventas'))
    <div class="col-lg-4 col-md-6 mb-3">
        <div class="stat-hero">
            <i class="fas fa-cash-register bg-icon"></i>
            <div class="label">Ventas de hoy</div>
            <div class="value">S/ {{ number_format($ventasHoy, 2) }}</div>
            @if($variacionVentas !== null)
                <span class="delta {{ $variacionVentas > 0 ? 'up' : ($variacionVentas < 0 ? 'down' : 'flat') }}">
                    <i class="fas fa-{{ $variacionVentas >= 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                    {{ abs($variacionVentas) }}% vs. ayer
                </span>
            @else
                <span class="delta flat">Sin ventas ayer para comparar</span>
            @endif
            <a href="{{ route('ventas.index') }}" class="foot-link">Ver todas las ventas →</a>
        </div>
    </div>
    <div class="col-lg-2 col-md-6 col-6 mb-3">
        <div class="stat-card acc-info">
            <div>
                <div class="label">Ventas del mes</div>
                <div class="value">S/ {{ number_format($ventasMes, 0) }}</div>
            </div>
            <a href="{{ route('ventas.index') }}" class="foot-link">Ver detalle →</a>
        </div>
    </div>
    @endif

    @if($u->hasModulo('compras'))
    <div class="col-lg-2 col-md-6 col-6 mb-3">
        <div class="stat-card acc-warn">
            <div>
                <div class="label">Compras del mes</div>
                <div class="value">S/ {{ number_format($comprasMes, 0) }}</div>
            </div>
            <a href="{{ route('compras.index') }}" class="foot-link">Ver compras →</a>
        </div>
    </div>
    @endif

    @if($u->isAdmin())
    <div class="col-lg-2 col-md-6 col-6 mb-3">
        <div class="stat-card {{ $gananciaMes >= 0 ? 'acc-success' : 'acc-danger' }}">
            <div>
                <div class="label">Ganancia del mes</div>
                <div class="value">S/ {{ number_format($gananciaMes, 0) }}</div>
            </div>
            <span class="foot-link" style="color:#8a8a9d;cursor:default;">Ventas − Compras</span>
        </div>
    </div>
    @endif

    @if($u->hasModulo('reportes') || $u->isAdmin())
    <div class="col-lg-2 col-md-6 col-6 mb-3">
        <div class="stat-card acc-info">
            <div>
                <div class="label">Registros hoy</div>
                <div class="value">{{ $registrosHoy }}</div>
            </div>
            <a href="{{ route('movimientos.index') }}" class="foot-link">Ver movimientos →</a>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ══════════ ALERTAS ══════════ --}}
@if($veAlertas)
<div class="section-heading">
    <span class="sh-icon"><i class="fas fa-bell"></i></span> Alertas
</div>
@if($totalAlertas > 0)
    <div class="alert-banner warn">
        <i class="fas fa-exclamation-triangle ab-icon"></i>
        <div class="ab-text">
            <strong>{{ $totalAlertas }} {{ $totalAlertas === 1 ? 'producto necesita' : 'productos necesitan' }} reposición</strong>
            <span>{{ $productosStockBajo }} producto(s) terminado(s) y {{ $materiaStockBaja }} insumo(s) están por debajo del stock mínimo.</span>
        </div>
        @if($u->hasModulo('compras'))
        <a href="{{ route('ordenes-automaticas.index') }}" class="btn btn-warning btn-sm ab-action">Generar órdenes</a>
        @endif
    </div>
@else
    <div class="alert-banner ok">
        <i class="fas fa-check-circle ab-icon"></i>
        <div class="ab-text">
            <strong>Todo en orden</strong>
            <span>Ningún producto ni insumo está por debajo de su stock mínimo.</span>
        </div>
    </div>
@endif
@endif

{{-- ══════════ INDICADORES DE GESTIÓN ══════════ --}}
@if($veIndicadores)
<div class="section-heading">
    <span class="sh-icon"><i class="fas fa-bullseye"></i></span> Indicadores de gestión
    <small>qué tan bien está funcionando el sistema</small>
</div>
<div class="row">
    <div class="col-lg-3 col-6 mb-3">
        <div class="kpi-card">
            @php $estadoExact = $exactitudInventario >= 90 ? 'good' : ($exactitudInventario >= 70 ? 'mid' : 'bad'); @endphp
            <div class="kpi-ring {{ $estadoExact }}">{{ $exactitudInventario }}%</div>
            <div class="kpi-title">Exactitud del Inventario</div>
            <div class="kpi-desc">Qué tanto coincide tu stock real con lo que dice el sistema</div>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-3">
        <div class="kpi-card">
            <div class="kpi-ring neutral">{{ $registrosHoy }}</div>
            <div class="kpi-title">Capacidad de Procesamiento</div>
            <div class="kpi-desc">Operaciones registradas hoy en todo el sistema</div>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-3">
        <div class="kpi-card">
            <div class="kpi-ring neutral">{{ $rotacionStock->count() }}</div>
            <div class="kpi-title">Rotación de Stock</div>
            <div class="kpi-desc">Productos distintos vendidos este mes</div>
        </div>
    </div>
    <div class="col-lg-3 col-6 mb-3">
        <div class="kpi-card">
            @if($reduccionTiemposMuertos !== null)
                @php $estadoTiempo = $reduccionTiemposMuertos >= 30 ? 'good' : ($reduccionTiemposMuertos >= 10 ? 'mid' : 'bad'); @endphp
                <div class="kpi-ring {{ $estadoTiempo }}">{{ $reduccionTiemposMuertos > 0 ? '−' : '' }}{{ abs($reduccionTiemposMuertos) }}%</div>
                <div class="kpi-title">Reducción de Tiempos Muertos</div>
                <div class="kpi-desc">Comparado con el tiempo que tomaba antes, a mano</div>
            @else
                <div class="kpi-ring neutral"><i class="fas fa-hourglass-half"></i></div>
                <div class="kpi-title">Reducción de Tiempos Muertos</div>
                <div class="kpi-desc">
                    Aún falta cargar el tiempo manual (pre-test).
                    <a href="{{ route('tiempos-operacion.index') }}">Cargarlo aquí →</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ══════════ GRÁFICO Y TOP PRODUCTOS ══════════ --}}
@if($u->hasModulo('ventas'))
<div class="row">
    <div class="col-md-8 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar mr-2 text-primary"></i>Ventas últimos 7 días</h5>
            </div>
            <div class="card-body">
                <canvas id="chartVentas" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-trophy mr-2 text-warning"></i>Más vendidos (mes)</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($topProductos as $i => $p)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <span class="badge badge-{{ ['primary','success','info','warning','secondary'][$i] }} mr-2">{{ $i+1 }}</span>
                            {{ $p->nombre }}
                        </span>
                        <span class="badge badge-light">{{ $p->total_vendido }} uds</span>
                    </li>
                    @empty
                    <li class="list-group-item text-muted text-center">Sin ventas este mes</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ══════════ ROTACIÓN Y ÚLTIMAS VENTAS ══════════ --}}
<div class="row">
    @if($u->hasModulo('inventario') || $u->hasModulo('reportes') || $u->isAdmin())
    <div class="col-md-{{ $u->hasModulo('ventas') ? 5 : 12 }} mb-3">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-sync-alt mr-2 text-success"></i>Rotación de Stock (mes)</h5>
                <a href="{{ route('kardex.rotacion') }}" class="btn btn-sm btn-outline-success">Ver completo</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 table-sm">
                    <thead class="bg-light">
                        <tr><th>Producto</th><th class="text-center">Vendido</th><th class="text-center">Stock</th></tr>
                    </thead>
                    <tbody>
                        @forelse($rotacionStock as $p)
                        <tr>
                            <td>{{ $p->nombre }}</td>
                            <td class="text-center font-weight-bold text-success">{{ $p->total_vendido }}</td>
                            <td class="text-center">{{ $p->stock_actual }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Sin ventas este mes</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if($u->hasModulo('ventas'))
    <div class="col-md-7 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-history mr-2 text-success"></i>Últimas ventas</h5>
                <a href="{{ route('ventas.create') }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus mr-1"></i>Nueva venta
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr><th>Nro.</th><th>Cliente</th><th>Pago</th><th>Total</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        @forelse($ultimasVentas as $v)
                        <tr>
                            <td><a href="{{ route('ventas.show', $v) }}" class="js-ver-detalle" data-titulo-detalle="Comprobante {{ $v->numero_venta }}">{{ $v->numero_venta }}</a></td>
                            <td>{{ $v->cliente->nombre ?? 'General' }}</td>
                            <td><span class="badge badge-light">{{ ucfirst($v->tipo_pago) }}</span></td>
                            <td class="font-weight-bold">S/ {{ number_format($v->total, 2) }}</td>
                            <td>
                                <span class="badge badge-{{ $v->estado === 'completada' ? 'success' : 'danger' }}">
                                    {{ ucfirst($v->estado) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay ventas aún</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@if(!$vePonderadoNegocio && !$veAlertas && !$veIndicadores)
<div class="alert-banner ok">
    <i class="fas fa-info-circle ab-icon"></i>
    <div class="ab-text">
        <strong>Todavía no tienes módulos con reportes asignados</strong>
        <span>Usa el menú de la izquierda para ir a las secciones a las que sí tienes acceso.</span>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
@if($u->hasModulo('ventas'))
const labels = @json($ventasSemana->pluck('fecha'));
const data   = @json($ventasSemana->pluck('total'));

new Chart(document.getElementById('chartVentas'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Ventas (S/)',
            data,
            backgroundColor: 'rgba(181,69,27,0.7)',
            borderColor: 'rgba(181,69,27,1)',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + v } } }
    }
});
@endif
</script>
@endpush
