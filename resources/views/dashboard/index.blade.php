@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
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
    /* Panel del almacén */
    .almacen-panel { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); overflow: hidden; }
    .almacen-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .6rem; padding: .9rem 1.3rem; border-bottom: 1px solid #f0ece6; }
    .almacen-acciones { display: flex; flex-wrap: wrap; gap: .4rem; }
    .almacen-lista { padding: .4rem 1.3rem .8rem; }
    .almacen-item { display: grid; grid-template-columns: minmax(150px, 1.4fr) 1.2fr auto auto; gap: 1rem; align-items: center; padding: .55rem 0; border-bottom: 1px dashed #eee; font-size: .88rem; }
    .almacen-item:last-child { border-bottom: none; }
    .ai-nombre { font-weight: 700; color: #1a1a2e; }
    .ai-barra { height: 8px; border-radius: 6px; background: #eef0f3; overflow: hidden; }
    .ai-fill { height: 100%; border-radius: 6px; }
    .ai-fill.rojo { background: #e74c3c; } .ai-fill.ambar { background: #f39c12; }
    .ai-dato { color: #6c757d; font-size: .8rem; white-space: nowrap; }
    .ai-links a { color: #8a8a9d; margin-left: .6rem; } .ai-links a:hover { color: #b5451b; }
    .almacen-vacio { padding: 1rem 1.3rem; color: #8a8a9d; }
    body.dark-mode .almacen-panel { background: #1f1f33; }
    body.dark-mode .almacen-head { border-bottom-color: #33334d; color: #f0f0f7; }
    body.dark-mode .almacen-item { border-bottom-color: #33334d; }
    body.dark-mode .ai-nombre { color: #f0f0f7; }
    body.dark-mode .ai-barra { background: #33334d; }
    @media (max-width: 700px) { .almacen-item { grid-template-columns: 1fr auto; } .ai-barra { grid-column: 1 / -1; } }


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
    .kpi-card .kpi-desc a { color: #b5451b; font-weight: 700; }
    body.dark-mode .kpi-card .kpi-desc a { color: #ff9d6e; }
    body.dark-mode .kpi-ring { border-color: #33334d; }
    body.dark-mode .kpi-ring.good    { border-color: #2ecc71; color: #6ee7a5; }
    body.dark-mode .kpi-ring.mid     { border-color: #f39c12; color: #ffc673; }
    body.dark-mode .kpi-ring.bad     { border-color: #e74c3c; color: #ff9b8f; }
    body.dark-mode .kpi-ring.neutral { border-color: #33334d; color: #9a9ac0; }

    /* ══════════ Dashboard del almacén ══════════ */
    /* Accesos rápidos: botones grandes a las tareas del día */
    .acciones-rapidas { display: grid; grid-template-columns: repeat(var(--columnas, 4), 1fr); gap: .8rem; margin-bottom: 1.4rem; }
    @media (max-width: 991px) { .acciones-rapidas { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px) { .acciones-rapidas { grid-template-columns: 1fr; } }
    .accion {
        display: flex; align-items: center; gap: .85rem; padding: 1rem 1.1rem; border-radius: 14px;
        background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,.05); text-decoration: none !important;
        border: 1.5px solid transparent; transition: border-color .15s, transform .15s, box-shadow .15s;
    }
    .accion:hover { border-color: #e2b49c; transform: translateY(-2px); box-shadow: 0 8px 22px rgba(181,69,27,.12); }
    .accion:focus-visible { outline: 2px solid #b5451b; outline-offset: 2px; }
    .accion .ac-ico {
        width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        background: #f4e9e3; color: #b5451b; font-size: 1.15rem;
    }
    .accion.principal .ac-ico { background: #b5451b; color: #fff; }
    .accion .ac-txt strong { display: block; color: #1a1a2e; font-size: .92rem; line-height: 1.2; }
    .accion .ac-txt span { display: block; color: #8a8a9d; font-size: .74rem; margin-top: .15rem; }

    /* Tarjetas del almacén */
    .alm-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1.15rem 1.3rem; height: 100%; display: flex; flex-direction: column; }
    .alm-card .ac-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .8rem; }
    .alm-card .ac-title { font-size: .78rem; color: #8a8a9d; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
    .alm-card .ac-title i { color: #b5451b; margin-right: .35rem; }
    .alm-card .ac-foot { margin-top: auto; padding-top: .8rem; font-size: .78rem; display: flex; flex-wrap: wrap; gap: .3rem 1rem; }
    .alm-card .ac-foot a { color: #b5451b; font-weight: 700; text-decoration: none; }
    .alm-card .ac-foot a:hover { text-decoration: underline; }

    .hoy-cifras { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; }
    .hoy-cifra { background: #faf8f6; border-radius: 10px; padding: .6rem .5rem; text-align: center; }
    .hoy-cifra b { display: block; font-size: 1.5rem; font-weight: 800; line-height: 1.1; }
    .hoy-cifra small { font-size: .72rem; color: #8a8a9d; font-weight: 700; }
    .hoy-cifra.ent b { color: #1e8e5a; } .hoy-cifra.sal b { color: #b3261e; } .hoy-cifra.aju b { color: #a5680d; }
    .hoy-nota { font-size: .78rem; color: #6c757d; margin-top: .7rem; }

    .per-fila { display: flex; align-items: center; gap: 1rem; }
    .per-valor { font-size: 2.2rem; font-weight: 800; line-height: 1; }
    .per-valor.good { color: #1e8e5a; } .per-valor.mid { color: #a5680d; } .per-valor.bad { color: #b3261e; }
    .per-detalle { font-size: .8rem; color: #6c757d; line-height: 1.35; }
    .per-tendencia { display: flex; align-items: flex-end; gap: 6px; height: 46px; margin-top: .9rem; }
    .per-barra { flex: 1; min-width: 14px; border-radius: 4px 4px 0 0; background: #f4e9e3; position: relative; }
    .per-barra.ultimo { background: #b5451b; }
    .per-barra.cero { background: #c8f0d8; height: 4px !important; }
    .per-eje { display: flex; gap: 6px; font-size: .64rem; color: #adb5bd; margin-top: .25rem; }
    .per-eje span { flex: 1; min-width: 14px; text-align: center; }
    .per-vacio { color: #6c757d; font-size: .85rem; }

    .recibir-lista { list-style: none; margin: 0; padding: 0; font-size: .84rem; }
    .recibir-lista li { display: flex; justify-content: space-between; gap: .5rem; padding: .4rem 0; border-bottom: 1px dashed #eee; }
    .recibir-lista li:last-child { border-bottom: none; }
    .recibir-lista .rl-prov { font-weight: 700; color: #1a1a2e; }
    .recibir-lista .rl-sub { color: #8a8a9d; font-size: .75rem; }
    .recibir-badge { display: inline-block; font-size: .72rem; font-weight: 800; padding: .15rem .55rem; border-radius: 999px; background: #fff4e5; color: #a5680d; }
    .recibir-badge.gris { background: #f1f1f4; color: #6c757d; }

    .ai-dias { font-size: .72rem; font-weight: 800; padding: .1rem .45rem; border-radius: 999px; white-space: nowrap; }
    .ai-dias.urgente { background: #fde8e6; color: #b3261e; }
    .ai-dias.pronto  { background: #fff4e5; color: #a5680d; }
    .ai-tipo { font-size: .66rem; font-weight: 700; color: #8a8a9d; text-transform: uppercase; letter-spacing: .04em; margin-left: .3rem; }
    .almacen-item { grid-template-columns: minmax(160px, 1.4fr) 1.1fr auto auto auto; }

    body.dark-mode .accion, body.dark-mode .alm-card { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    body.dark-mode .accion .ac-txt strong { color: #f0f0f7; }
    body.dark-mode .accion .ac-ico { background: rgba(181,69,27,.22); color: #ff9d6e; }
    body.dark-mode .accion.principal .ac-ico { background: #b5451b; color: #fff; }
    body.dark-mode .hoy-cifra { background: #26263d; }
    body.dark-mode .recibir-lista .rl-prov { color: #f0f0f7; }
    body.dark-mode .recibir-lista li { border-bottom-color: #33334d; }
    body.dark-mode .per-barra { background: rgba(181,69,27,.25); }
    body.dark-mode .per-barra.ultimo { background: #b5451b; }

    /* Listas cortas (producción, por agotarse) */
    .lista-corta { list-style: none; margin: 0; padding: 0; font-size: .86rem; }
    .lista-corta li { display: flex; justify-content: space-between; align-items: center; gap: .6rem; padding: .45rem 0; border-bottom: 1px dashed #eee; }
    .lista-corta li:last-child { border-bottom: none; }
    .lista-corta .lc-nombre { font-weight: 700; color: #1a1a2e; }
    .lista-corta .lc-dato { color: #6c757d; font-size: .8rem; white-space: nowrap; }

    body.dark-mode .lista-corta .lc-nombre { color: #f0f0f7; }
    body.dark-mode .lista-corta li { border-bottom-color: #33334d; }
</style>
@endpush

@section('content')

@php
    $u = auth()->user();
    // Qué bloques ve este usuario (según sus pantallas permitidas)
    $veInventario = $u->hasModulo('inventario');
    $veCompras    = $u->hasModulo('compras');
    $veProduccion = $u->hasModulo('produccion');
    $veVentas     = $u->hasModulo('ventas');
    $veReportes   = $u->hasModulo('reportes');
    $veStock      = $veInventario || $veCompras || $u->hasPermiso('reportes.stock');
    $totalAlertas = $productosStockBajo + $materiaStockBaja;
    $fmt = fn($n) => rtrim(rtrim(number_format($n, 2), '0'), '.') ?: '0';

    // Accesos rápidos: se arman con las pantallas del usuario, en orden de prioridad
    // (almacén → producción → ventas → reportes) y se muestran hasta 6.
    $puedeVender = $veVentas && $u->hasPermiso('catalogo.productos');
    $acciones = array_values(array_filter([
        $u->hasPermiso('inventario.materia_prima') ? ['materia-prima.index', 'fa-boxes', 'Insumos y stock', 'Ver stock, ajustar o editar'] : null,
        $u->hasPermiso('inventario.conteo')        ? ['conteo-fisico.index', 'fa-clipboard-check', 'Conteo físico', 'Contar y comparar con el sistema'] : null,
        $u->hasPermiso('compras.compras')          ? ['compras.create', 'fa-cart-plus', 'Registrar compra', 'Nueva compra a un proveedor'] : null,
        $u->hasPermiso('produccion.produccion')    ? ['produccion.create', 'fa-industry', 'Registrar producción', 'Hornear según la receta'] : null,
        $puedeVender                               ? ['productos.index', 'fa-shopping-basket', 'Vender', 'Carrito de productos'] : null,
        $u->hasPermiso('clientes')                 ? ['clientes.index', 'fa-user-friends', 'Clientes', 'Buscar o registrar clientes'] : null,
        $veVentas && !$puedeVender                 ? ['ventas.index', 'fa-cash-register', 'Ventas', 'Historial de ventas'] : null,
        $u->hasPermiso('compras.ordenes')          ? ['ordenes-automaticas.index', 'fa-robot', 'Órdenes automáticas', 'Reposición sugerida'] : null,
        $u->hasPermiso('reportes.kardex')          ? ['reportes.kardex', 'fa-file-invoice', 'Kardex', 'Entradas, salidas y saldo'] : null,
        $u->hasPermiso('produccion.recetas')       ? ['produccion.recetas', 'fa-book', 'Recetas', 'Ingredientes de cada producto'] : null,
        $u->hasPermiso('reportes.stock')           ? ['reportes.stock', 'fa-warehouse', 'Reporte de stock', 'Semáforo y exportar'] : null,
        $u->hasPermiso('reportes.resumen')         ? ['reportes.index', 'fa-chart-pie', 'Resumen de reportes', 'Cifras clave'] : null,
    ]));
    $acciones = array_slice($acciones, 0, 6);
@endphp

<div class="dash-greeting">
    <h2>Hola, {{ $u->apodo ?? explode(' ', $u->nombre)[0] }} 👋</h2>
    <p>{{ \Carbon\Carbon::now()->locale('es')->translatedFormat('l, d \d\e F \d\e Y') }}</p>
</div>

{{-- ══════════ ACCESOS RÁPIDOS (según las pantallas del usuario) ══════════ --}}
@if($acciones)
@php $columnas = count($acciones) <= 4 ? count($acciones) : (count($acciones) === 5 ? 5 : 3); @endphp
<div class="acciones-rapidas" style="--columnas: {{ $columnas }}">
    @foreach($acciones as [$ruta, $icono, $titulo, $desc])
        <a href="{{ route($ruta) }}" class="accion {{ $loop->first ? 'principal' : '' }}">
            <span class="ac-ico"><i class="fas {{ $icono }}"></i></span>
            <span class="ac-txt"><strong>{{ $titulo }}</strong><span>{{ $desc }}</span></span>
        </a>
    @endforeach
</div>
@endif

{{-- ══════════ ALMACÉN: hoy · PER · por recibir ══════════ --}}
@if($veInventario || $veCompras || $veReportes)
<div class="row">
    @if($veInventario)
    <div class="col-lg-4 mb-3">
        <div class="alm-card">
            <div class="ac-head"><span class="ac-title"><i class="fas fa-warehouse"></i>Hoy en el almacén</span></div>
            <div class="hoy-cifras">
                <div class="hoy-cifra ent"><b>{{ $almacenHoy['entradas'] }}</b><small>Entradas</small></div>
                <div class="hoy-cifra sal"><b>{{ $almacenHoy['salidas'] }}</b><small>Salidas</small></div>
                <div class="hoy-cifra aju"><b>{{ $almacenHoy['ajustes'] }}</b><small>Ajustes</small></div>
            </div>
            <div class="hoy-nota">
                @if($almacenHoy['ultimo'])
                    {{ $almacenHoy['insumos'] }} insumo(s) con movimiento · último a las
                    {{ \Carbon\Carbon::parse($almacenHoy['ultimo'])->format('H:i') }}
                @else
                    Todavía no hay movimientos de insumos hoy.
                @endif
            </div>
            @if($u->hasPermiso('inventario.movimientos'))
            <div class="ac-foot"><a href="{{ route('movimientos.index') }}">Ver movimientos del día →</a></div>
            @endif
        </div>
    </div>
    @endif

    @if($veInventario || $veReportes)
    <div class="col-lg-4 mb-3">
        <div class="alm-card">
            <div class="ac-head"><span class="ac-title"><i class="fas fa-bullseye"></i>Errores de registro (PER)</span></div>
            @if($ultimoConteo)
                @php $estadoPer = $ultimoConteo->per == 0 ? 'good' : ($ultimoConteo->per <= 20 ? 'mid' : 'bad'); @endphp
                <div class="per-fila">
                    <div class="per-valor {{ $estadoPer }}">{{ $fmt($ultimoConteo->per) }}%</div>
                    <div class="per-detalle">
                        <strong>{{ $ultimoConteo->con_diferencia }} de {{ $ultimoConteo->verificados }}</strong> insumos no coinciden<br>
                        Conteo del {{ \Carbon\Carbon::parse($ultimoConteo->fecha)->locale('es')->translatedFormat('d M, H:i') }}
                    </div>
                </div>
                @if($conteos->count() > 1)
                    @php $maxPer = max(1, $conteos->max('per')); @endphp
                    <div class="per-tendencia" title="PER de los últimos conteos (menos es mejor)">
                        @foreach($conteos as $c)
                            <div class="per-barra {{ $loop->last ? 'ultimo' : '' }} {{ $c->per == 0 ? 'cero' : '' }}"
                                 style="height: {{ max(8, round($c->per / $maxPer * 100)) }}%"
                                 title="{{ \Carbon\Carbon::parse($c->fecha)->format('d/m') }}: {{ $fmt($c->per) }}%"></div>
                        @endforeach
                    </div>
                    <div class="per-eje">
                        @foreach($conteos as $c)<span>{{ \Carbon\Carbon::parse($c->fecha)->format('d/m') }}</span>@endforeach
                    </div>
                @endif
            @else
                <div class="per-vacio">
                    <i class="fas fa-info-circle mr-1"></i>Aún no hay conteos físicos.
                    El PER se calcula con el primer conteo: insumos que no coinciden ÷ insumos contados × 100.
                </div>
            @endif
            <div class="ac-foot">
                @if($u->hasPermiso('inventario.conteo'))
                    <a href="{{ route('conteo-fisico.index') }}">Hacer conteo →</a>
                    @if($ultimoConteo)<a href="{{ route('conteo-fisico.historial') }}">Historial</a>@endif
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($veCompras)
    <div class="col-lg-4 mb-3">
        <div class="alm-card">
            <div class="ac-head">
                <span class="ac-title"><i class="fas fa-truck-loading"></i>Por recibir</span>
                @if($comprasPendientes->isNotEmpty())<span class="recibir-badge">{{ $comprasPendientes->count() }} compra(s)</span>@endif
            </div>
            @if($comprasPendientes->isNotEmpty())
                <ul class="recibir-lista">
                    @foreach($comprasPendientes->take(3) as $c)
                        <li>
                            <span>
                                <span class="rl-prov">{{ $c->proveedor->nombre ?? 'Proveedor' }}</span><br>
                                <span class="rl-sub">Pedida el {{ \Carbon\Carbon::parse($c->fecha_compra)->format('d/m') }}{{ $c->numero_doc ? ' · ' . $c->numero_doc : '' }}</span>
                            </span>
                            <span class="font-weight-bold">S/ {{ number_format($c->total, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="per-vacio"><i class="fas fa-check-circle text-success mr-1"></i>No hay compras pendientes de recibir.</div>
            @endif
            <div class="hoy-nota">
                @if($ordenesPendientes > 0)
                    <span class="recibir-badge gris">{{ $ordenesPendientes }}</span> orden(es) automática(s) por convertir en compra
                    @if($ordenesSinProveedor > 0)· {{ $ordenesSinProveedor }} sin proveedor @endif
                @else
                    Sin órdenes automáticas pendientes.
                @endif
            </div>
            <div class="ac-foot">
                @if($u->hasPermiso('compras.compras') && $comprasPendientes->isNotEmpty())<a href="{{ route('compras.index') }}">Recibir compras →</a>@endif
                @if($u->hasPermiso('compras.ordenes') && $ordenesPendientes > 0)<a href="{{ route('ordenes-automaticas.index') }}">Ver órdenes</a>@endif
            </div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ══════════ STOCK BAJO (almacén, compras o reporte de stock) ══════════ --}}
@if($veStock)
    @if($totalAlertas > 0)
        <div class="alert-banner warn mt-1">
            <i class="fas fa-exclamation-triangle ab-icon"></i>
            <div class="ab-text">
                <strong>{{ $totalAlertas }} {{ $totalAlertas === 1 ? 'ítem necesita' : 'ítems necesitan' }} reposición</strong>
                <span>{{ $materiaStockBaja }} insumo(s) y {{ $productosStockBajo }} producto(s) terminado(s) están en o por debajo de su stock mínimo.</span>
            </div>
            @if($u->hasPermiso('compras.ordenes') && $materiaStockBaja > 0)
            <a href="{{ route('ordenes-automaticas.index') }}" class="btn btn-warning btn-sm ab-action">Generar órdenes</a>
            @endif
        </div>
    @else
        <div class="alert-banner ok mt-1">
            <i class="fas fa-check-circle ab-icon"></i>
            <div class="ab-text">
                <strong>Todo en orden</strong>
                <span>Ningún insumo ni producto está por debajo de su stock mínimo.</span>
            </div>
        </div>
    @endif

    @if($stockCritico->isNotEmpty())
    <div class="card almacen-panel mt-3 mb-3">
        <div class="almacen-head">
            <strong><i class="fas fa-boxes-stacked mr-2"></i>Lo más urgente de reponer</strong>
            <div class="almacen-acciones">
                @if($u->hasPermiso('reportes.stock'))<a href="{{ route('reportes.stock', ['stock' => 'bajo']) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-warehouse mr-1"></i>Reporte de stock</a>@endif
            </div>
        </div>
        <div class="almacen-lista">
            @foreach($stockCritico as $c)
                @php $pct = $c->minimo > 0 ? min(100, round(($c->stock / $c->minimo) * 100)) : 0; @endphp
                <div class="almacen-item">
                    <div class="ai-nombre">
                        {{ $c->nombre }}
                        @if($c->tipo === 'producto')<span class="ai-tipo">producto</span>@endif
                        @if($c->stock <= 0)<span class="badge-soft badge-soft-danger ml-1">Agotado</span>@else<span class="badge-soft badge-soft-warning ml-1">Bajo</span>@endif
                    </div>
                    <div class="ai-barra"><div class="ai-fill {{ $c->stock <= 0 ? 'rojo' : 'ambar' }}" style="width: {{ max($pct, 3) }}%"></div></div>
                    <div class="ai-dato">{{ $fmt($c->stock) }} / {{ $fmt($c->minimo) }} {{ $c->unidad }}</div>
                    <div>
                        @if($c->dias !== null)
                            <span class="ai-dias {{ $c->dias < 1 ? 'urgente' : 'pronto' }}" title="Según el consumo promedio de los últimos 7 días">
                                {{ $c->dias < 1 ? 'menos de 1 día' : '≈ ' . floor($c->dias) . ' día' . (floor($c->dias) == 1 ? '' : 's') }}
                            </span>
                        @endif
                    </div>
                    <div class="ai-links">
                        @if($u->hasPermiso('reportes.kardex'))<a href="{{ route('reportes.kardex', ['item' => $c->tipo.'-'.$c->id]) }}" title="Ver kardex"><i class="fas fa-file-invoice"></i></a>@endif
                        @if($c->tipo === 'materia' && $u->hasPermiso('inventario.materia_prima'))<a href="{{ route('materia-prima.ajuste', $c->id) }}" title="Ajustar stock"><i class="fas fa-balance-scale"></i></a>@endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
@endif

{{-- ══════════ PRODUCCIÓN ══════════ --}}
@if($veProduccion)
<div class="section-heading">
    <span class="sh-icon"><i class="fas fa-industry"></i></span> Producción
</div>
<div class="row">
    <div class="col-lg-6 mb-3">
        <div class="alm-card">
            <div class="ac-head">
                <span class="ac-title"><i class="fas fa-fire"></i>Producido hoy</span>
                @if($producidoHoy->isNotEmpty())<span class="recibir-badge gris">{{ $producidoHoy->sum('cantidad') }} uds</span>@endif
            </div>
            @if($producidoHoy->isNotEmpty())
                <ul class="lista-corta">
                    @foreach($producidoHoy->take(6) as $p)
                        <li><span class="lc-nombre">{{ $p->nombre }}</span><span class="lc-dato">{{ $p->cantidad }} uds</span></li>
                    @endforeach
                </ul>
            @else
                <div class="per-vacio">Todavía no se registra producción hoy.</div>
            @endif
            <div class="ac-foot">
                @if($u->hasPermiso('produccion.produccion'))<a href="{{ route('produccion.index') }}">Ver producciones →</a>@endif
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-3">
        <div class="alm-card">
            <div class="ac-head">
                <span class="ac-title"><i class="fas fa-bread-slice"></i>Toca producir</span>
                @if($porProducir->isNotEmpty())<span class="recibir-badge">{{ $porProducir->count() }} producto(s)</span>@endif
            </div>
            @if($porProducir->isNotEmpty())
                <ul class="lista-corta">
                    @foreach($porProducir as $p)
                        <li>
                            <span class="lc-nombre">{{ $p->nombre }}
                                @if($p->stock_actual <= 0)<span class="badge-soft badge-soft-danger ml-1">Agotado</span>@endif
                            </span>
                            <span class="lc-dato">{{ $p->stock_actual }} / mín. {{ $p->stock_minimo }} uds</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="per-vacio"><i class="fas fa-check-circle text-success mr-1"></i>Todos los productos están sobre su mínimo.</div>
            @endif
            <div class="ac-foot">
                @if($u->hasPermiso('produccion.produccion') && $porProducir->isNotEmpty())<a href="{{ route('produccion.create') }}">Registrar producción →</a>@endif
            </div>
        </div>
    </div>
</div>
@endif

{{-- ══════════ VENTAS ══════════ --}}
@if($veVentas)
<div class="section-heading">
    <span class="sh-icon"><i class="fas fa-store"></i></span> Ventas
</div>
@php $soloVentas = !$veCompras && !$u->isAdmin(); /* sin más tarjetas: las dos ocupan todo el ancho */ @endphp
<div class="row">
    <div class="{{ $soloVentas ? 'col-lg-6' : 'col-lg-4' }} col-md-6 mb-3">
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
    <div class="{{ $soloVentas ? 'col-lg-6' : 'col-lg-2' }} col-md-6 {{ $soloVentas ? '' : 'col-6' }} mb-3">
        <div class="stat-card acc-info">
            <div><div class="label">Ventas del mes</div><div class="value">S/ {{ number_format($ventasMes, 0) }}</div></div>
            <a href="{{ route('ventas.index') }}" class="foot-link">Ver detalle →</a>
        </div>
    </div>
    @if($veCompras)
    <div class="col-lg-2 col-md-6 col-6 mb-3">
        <div class="stat-card acc-warn">
            <div><div class="label">Compras del mes</div><div class="value">S/ {{ number_format($comprasMes, 0) }}</div></div>
            @if($u->hasPermiso('compras.compras'))<a href="{{ route('compras.index') }}" class="foot-link">Ver compras →</a>@endif
        </div>
    </div>
    @endif
    @if($u->isAdmin())
    <div class="col-lg-4 col-md-6 col-6 mb-3">
        <div class="stat-card {{ $ventasMenosCompras >= 0 ? 'acc-success' : 'acc-danger' }}">
            <div><div class="label">Ventas − compras del mes</div><div class="value">S/ {{ number_format($ventasMenosCompras, 0) }}</div></div>
            <span class="foot-link" style="color:#8a8a9d;cursor:default;">Referencial: no descuenta otros gastos</span>
        </div>
    </div>
    @endif
</div>

<div class="row">
    <div class="col-md-8 mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar mr-2 text-primary"></i>Ventas de los últimos 7 días</h5>
            </div>
            <div class="card-body"><canvas id="chartVentas" height="110"></canvas></div>
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
                        <span><span class="badge badge-{{ ['primary','success','info','warning','secondary'][$i] }} mr-2">{{ $i+1 }}</span>{{ $p->nombre }}</span>
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

<div class="row">
    {{-- Para el mostrador: qué se está acabando (quien vende no ve el bloque de stock) --}}
    @if(!$veStock)
    <div class="col-md-4 mb-3">
        <div class="alm-card">
            <div class="ac-head">
                <span class="ac-title"><i class="fas fa-hourglass-half"></i>Por agotarse</span>
            </div>
            @if($porAgotarse->isNotEmpty())
                <ul class="lista-corta">
                    @foreach($porAgotarse as $p)
                        <li>
                            <span class="lc-nombre">{{ $p->nombre }}
                                @if($p->stock_actual <= 0)<span class="badge-soft badge-soft-danger ml-1">Agotado</span>@endif
                            </span>
                            <span class="lc-dato">quedan {{ $p->stock_actual }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="per-vacio"><i class="fas fa-check-circle text-success mr-1"></i>Hay stock de todos los productos.</div>
            @endif
        </div>
    </div>
    @endif
    <div class="{{ $veStock ? 'col-md-12' : 'col-md-8' }} mb-3">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-history mr-2 text-success"></i>Últimas ventas</h5>
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
                            <td><span class="badge badge-{{ $v->estado === 'completada' ? 'success' : 'danger' }}">{{ ucfirst($v->estado) }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay ventas aún</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Sin ningún módulo asignado todavía --}}
@if(!$acciones && !$veVentas && !$veProduccion && !$veStock && !$veReportes)
<div class="alert-banner ok">
    <i class="fas fa-info-circle ab-icon"></i>
    <div class="ab-text">
        <strong>Todavía no tienes pantallas asignadas</strong>
        <span>Pídele al administrador que te dé acceso a las secciones que necesitas.</span>
    </div>
</div>
@endif

@endsection

@push('scripts')
@if($veVentas)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;
    const semana = @json($ventasSemana);
    new Chart(document.getElementById('chartVentas'), {
        type: 'bar',
        data: {
            labels: semana.map(d => d.etiqueta),
            datasets: [{
                label: 'Ventas (S/)', data: semana.map(d => d.total),
                backgroundColor: 'rgba(181,69,27,0.7)', borderColor: 'rgba(181,69,27,1)', borderWidth: 2, borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => 'S/ ' + c.parsed.y.toFixed(2) } } },
            scales: { y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + v } } }
        }
    });
})();
</script>
@endif
@endpush
