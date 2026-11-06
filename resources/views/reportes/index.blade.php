@extends('layouts.app')
@section('title', 'Reportes')
@section('breadcrumb') <li class="breadcrumb-item active">Reportes</li> @endsection

@include('partials.reportes-estilos')

@push('styles')
<style>
    .rh-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .8rem; margin-bottom: 1.4rem; }
    .rh-kpi { background: #fff; border-radius: 14px; padding: .95rem 1.1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05); border-left: 4px solid var(--c, #b5451b); text-decoration: none !important; color: inherit; display: block; }
    .rh-kpi:hover { box-shadow: 0 6px 20px rgba(0,0,0,.09); color: inherit; }
    .rh-kpi small { display: block; font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #8a8a9d; }
    .rh-kpi b { display: block; font-size: 1.45rem; color: #1a1a2e; line-height: 1.25; margin: .15rem 0; }
    .rh-kpi span { font-size: .78rem; color: #8a8a9d; }
    body.dark-mode .rh-kpi { background: #1f1f33; }
    body.dark-mode .rh-kpi b { color: #f0f0f7; }

    .rep-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 1rem; }
    .rep-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1.2rem 1.3rem; display: flex; flex-direction: column; gap: .55rem;
        text-decoration: none !important; color: inherit; border: 1.5px solid transparent; transition: box-shadow .15s, border-color .15s; }
    .rep-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.09); border-color: #e9e4de; color: inherit; }
    body.dark-mode .rep-card { background: #1f1f33; }
    .rep-card .rc-top { display: flex; align-items: center; gap: .75rem; }
    .rep-card .rc-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: #fff;
        background: linear-gradient(135deg, #1a1a2e, var(--c, #b5451b)); flex-shrink: 0; }
    .rep-card h5 { margin: 0; font-weight: 800; font-size: 1rem; }
    .rep-card p { margin: 0; color: #8a8a9d; font-size: .84rem; line-height: 1.4; flex: 1; }
    .rep-card .rc-alert { display: inline-flex; align-items: center; gap: .35rem; font-size: .76rem; font-weight: 700; padding: .25rem .6rem; border-radius: 8px; width: fit-content; }
    .rc-alert.ok { background: #e5f6ec; color: #1e8e5a; } .rc-alert.warn { background: #fbe9e7; color: #c0392b; } .rc-alert.info { background: #eef4fb; color: #1c5d80; }
    body.dark-mode .rc-alert.ok { background: rgba(30,142,90,.18); color: #6ee7a5; }
    body.dark-mode .rc-alert.warn { background: rgba(192,57,43,.2); color: #ff9d8c; }
    body.dark-mode .rc-alert.info { background: rgba(52,152,219,.15); color: #9fd0f5; }
    .rep-card .rc-exp { display: flex; gap: .35rem; font-size: .72rem; color: #adb5bd; }
</style>
@endpush

@section('content')
@php
    $u = auth()->user();
    $totalBajo = $insumosStockBajo + $productosStockBajo;
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-chart-pie mr-2 text-secondary"></i>Reportes</h2>
        <p>Cómo está el inventario hoy y todos los reportes para revisarlo o sustentarlo · {{ ucfirst(today()->locale('es')->translatedFormat('l d \d\e F')) }}</p>
    </div>
</div>

<div class="rh-kpis">
    @if($u->hasPermiso('reportes.stock'))
        <a href="{{ route('reportes.stock') }}" class="rh-kpi" style="--c:#1e8e5a"><small>Valor del almacén</small><b>S/ {{ number_format($valorInsumos + $valorProductos, 2) }}</b><span>insumos S/ {{ number_format($valorInsumos, 0) }} · productos S/ {{ number_format($valorProductos, 0) }}</span></a>
        <a href="{{ route('reportes.stock', ['stock' => 'bajo']) }}" class="rh-kpi" style="--c:{{ $totalBajo ? '#c0392b' : '#1e8e5a' }}"><small>Stock bajo</small><b>{{ $totalBajo }}</b><span>{{ $insumosStockBajo }} insumo(s) · {{ $productosStockBajo }} producto(s)</span></a>
    @endif
    @if($u->hasPermiso('reportes.cierre'))
        <a href="{{ route('reportes.cierre') }}" class="rh-kpi" style="--c:{{ $cierreHoy ? '#1e8e5a' : '#d4880f' }}"><small>Cierre de hoy</small><b>{{ $cierreHoy ? 'Cerrado ' . $cierreHoy->updated_at->format('H:i') : 'Pendiente' }}</b><span>{{ $movimientosHoy }} movimiento(s) hoy{{ $sinCerrar ? ' · ' . $sinCerrar . ' día(s) atrás sin cerrar' : '' }}</span></a>
    @endif
    @if($u->hasPermiso('reportes.mermas'))
        <a href="{{ route('reportes.mermas') }}" class="rh-kpi" style="--c:#c0392b"><small>Mermas del mes</small><b>S/ {{ number_format($mermasMes, 2) }}</b><span>insumos perdidos</span></a>
    @endif
    @if($u->hasPermiso('reportes.compras'))
        <a href="{{ route('reportes.compras') }}" class="rh-kpi" style="--c:#d4880f"><small>Compras del mes</small><b>S/ {{ number_format($comprasMes, 2) }}</b><span>{{ $comprasPorRecibir }} por recibir</span></a>
    @endif
    @if($u->hasPermiso('reportes.ventas'))
        <a href="{{ route('reportes.ventas') }}" class="rh-kpi" style="--c:#27ae60"><small>Ventas del mes</small><b>S/ {{ number_format($ventasMes, 2) }}</b><span>{{ $ventasMesN }} venta(s) · IGV incluido</span></a>
    @endif
    @if($u->hasPermiso('inventario.conteo'))
        <a href="{{ route('conteo-fisico.historial') }}" class="rh-kpi" style="--c:{{ $per === null ? '#6c757d' : ($per <= 10 ? '#1e8e5a' : ($per <= 30 ? '#d4880f' : '#c0392b')) }}"><small>PER último conteo</small><b>{{ $per !== null ? $per . ' %' : '—' }}</b><span>{{ $fechaConteo ? $fechaConteo->format('d/m/Y') : 'sin conteos' }} · 0 % es lo ideal</span></a>
    @endif
</div>

<p class="cp-titulo"><i class="fas fa-folder-open"></i>Reportes</p>
<div class="rep-grid">
    @if($u->hasPermiso('reportes.cierre'))
    <a href="{{ route('reportes.cierre') }}" class="rep-card" style="--c:#1e8e5a">
        <div class="rc-top"><div class="rc-icon"><i class="fas fa-calendar-check"></i></div><h5>Cierre diario</h5></div>
        <p>Todos los insumos con saldo inicial, entradas, salidas, ajustes y saldo final del día. Se registra quién cerró y a qué hora.</p>
        <span class="rc-alert {{ $cierreHoy ? 'ok' : 'warn' }}"><i class="fas {{ $cierreHoy ? 'fa-lock' : 'fa-lock-open' }}"></i>{{ $cierreHoy ? 'Hoy ya se cerró' : 'Hoy todavía no se cierra' }}</span>
        <div class="rc-exp"><i class="fas fa-file-pdf"></i>PDF <i class="fas fa-file-excel ml-1"></i>Excel</div>
    </a>
    @endif
    @if($u->hasPermiso('reportes.stock'))
    <a href="{{ route('reportes.stock') }}" class="rep-card" style="--c:#2980b9">
        <div class="rc-top"><div class="rc-icon"><i class="fas fa-warehouse"></i></div><h5>Stock</h5></div>
        <p>Insumos y productos con su cantidad, días que alcanza, valorización y cuánto reponer.</p>
        <span class="rc-alert {{ $totalBajo ? 'warn' : 'ok' }}"><i class="fas {{ $totalBajo ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>{{ $totalBajo ? $totalBajo . ' con stock bajo' : 'Todo el stock en orden' }}</span>
        <div class="rc-exp"><i class="fas fa-file-pdf"></i>PDF <i class="fas fa-file-excel ml-1"></i>Excel</div>
    </a>
    @endif
    @if($u->hasPermiso('reportes.kardex'))
    <a href="{{ route('reportes.kardex') }}" class="rep-card" style="--c:#8e44ad">
        <div class="rc-top"><div class="rc-icon"><i class="fas fa-file-invoice"></i></div><h5>Kardex</h5></div>
        <p>Ficha cronológica de entradas, salidas y saldo de un insumo o producto, con su gráfico.</p>
        <span class="rc-alert info"><i class="fas fa-clock"></i>{{ $movimientosHoy }} movimiento(s) registrados hoy</span>
        <div class="rc-exp"><i class="fas fa-file-pdf"></i>PDF <i class="fas fa-file-excel ml-1"></i>Excel</div>
    </a>
    @endif
    @if($u->hasPermiso('reportes.mermas'))
    <a href="{{ route('reportes.mermas') }}" class="rep-card" style="--c:#c0392b">
        <div class="rc-top"><div class="rc-icon"><i class="fas fa-trash-alt"></i></div><h5>Mermas</h5></div>
        <p>Lo que se perdió por vencido, roto o mal estado: por insumo, por motivo y cuánto costó.</p>
        <span class="rc-alert {{ $mermasMes > 0 ? 'warn' : 'ok' }}"><i class="fas fa-coins"></i>Este mes: S/ {{ number_format($mermasMes, 2) }}</span>
        <div class="rc-exp"><i class="fas fa-file-pdf"></i>PDF <i class="fas fa-file-excel ml-1"></i>Excel</div>
    </a>
    @endif
    @if($u->hasPermiso('reportes.compras'))
    <a href="{{ route('reportes.compras') }}" class="rep-card" style="--c:#d4880f">
        <div class="rc-top"><div class="rc-icon"><i class="fas fa-shopping-basket"></i></div><h5>Compras</h5></div>
        <p>Gasto por proveedor e insumo, precio promedio y cómo subieron o bajaron los precios.</p>
        <span class="rc-alert info"><i class="fas fa-coins"></i>Este mes: S/ {{ number_format($comprasMes, 2) }}</span>
        <div class="rc-exp"><i class="fas fa-file-pdf"></i>PDF <i class="fas fa-file-excel ml-1"></i>Excel</div>
    </a>
    @endif
    @if($u->hasPermiso('inventario.conteo'))
    <a href="{{ route('conteo-fisico.historial') }}" class="rep-card" style="--c:#16a085">
        <div class="rc-top"><div class="rc-icon"><i class="fas fa-clipboard-check"></i></div><h5>Conteo físico y PER</h5></div>
        <p>Historial de conteos: insumos que no coincidieron con el sistema y la tendencia del PER.</p>
        @if($per !== null)
            <span class="rc-alert {{ $per <= 10 ? 'ok' : 'warn' }}"><i class="fas fa-percent"></i>Último PER: {{ $per }} %</span>
        @else
            <span class="rc-alert warn"><i class="fas fa-triangle-exclamation"></i>Todavía no hay conteos</span>
        @endif
    </a>
    @endif
    @if($u->hasPermiso('reportes.ventas'))
    <a href="{{ route('reportes.ventas') }}" class="rep-card" style="--c:#27ae60">
        <div class="rc-top"><div class="rc-icon"><i class="fas fa-chart-line"></i></div><h5>Ventas</h5></div>
        <p>Ingresos por día y por hora, productos más vendidos con su ganancia, medios de pago, clientes y vendedores.</p>
        <span class="rc-alert ok"><i class="fas fa-coins"></i>Este mes: S/ {{ number_format($ventasMes, 2) }}</span>
        <div class="rc-exp"><i class="fas fa-file-pdf"></i>PDF <i class="fas fa-file-excel ml-1"></i>Excel</div>
    </a>
    @elseif($u->hasModulo('ventas'))
    <a href="{{ route('ventas.exportar-excel') }}" class="rep-card" style="--c:#27ae60">
        <div class="rc-top"><div class="rc-icon"><i class="fas fa-file-excel"></i></div><h5>Historial de ventas</h5></div>
        <p>Descarga el historial completo de ventas en Excel, para evidencia o cuadrar caja.</p>
        <span class="rc-alert ok"><i class="fas fa-download"></i>Exportar ahora</span>
    </a>
    @endif
</div>
@endsection
