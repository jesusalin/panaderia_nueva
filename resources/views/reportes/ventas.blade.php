@extends('layouts.app')
@section('title', 'Reporte de ventas')
@section('breadcrumb')
    @if(auth()->user()->hasPermiso('reportes.resumen'))<li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>@endif
    <li class="breadcrumb-item active">Ventas</li>
@endsection

@include('partials.reportes-estilos')

@push('styles')
<style>
    .rc-tiempo { display: flex; align-items: flex-end; gap: 6px; height: 160px; padding-top: 1rem; overflow-x: auto; }
    .rc-tiempo .t { flex: 1 0 30px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .rc-tiempo .t span { width: 100%; max-width: 44px; background: linear-gradient(180deg, #34c27d, #1e8e5a); border-radius: 5px 5px 0 0; min-height: 2px; }
    .rc-tiempo .t.pico span { background: linear-gradient(180deg, #f0a43a, #d4880f); }
    .rc-tiempo .t em { font-style: normal; font-size: .64rem; color: #8a8a9d; margin-top: .25rem; white-space: nowrap; }
    .rc-tiempo .t b { font-size: .6rem; color: #6c757d; white-space: nowrap; margin-bottom: .15rem; }
    .rv-barra span { background: linear-gradient(90deg, #34c27d, #1e8e5a) !important; }
    .rv-medio { display: flex; align-items: center; gap: .6rem; }
    .rv-medio .ic { width: 30px; height: 30px; border-radius: 9px; color: #fff; display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; }
    .rv-pos { width: 24px; height: 24px; border-radius: 50%; background: #f3f0ec; color: #8a8a9d; font-size: .72rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; margin-right: .4rem; }
    .rv-pos.oro { background: #fff1d6; color: #a5680d; }
    body.dark-mode .rv-pos { background: #2c2c44; }
    .rv-margen { font-size: .74rem; font-weight: 800; border-radius: 10px; padding: .05rem .45rem; }
    .rv-margen.bien { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .rv-margen.medio { background: rgba(243,156,18,.15); color: #a5680d; }
    .rv-margen.mal { background: rgba(231,76,60,.12); color: #c0392b; }
</style>
@endpush

@section('content')
@php
    $q = array_filter(['periodo' => $periodo ?: null, 'desde' => request('desde'), 'hasta' => request('hasta'), 'vendedor' => $idVendedor]);
    $maxT = max(0.01, $porTiempo->max('total'));
    $maxH = max(0.01, $porHora->max('total'));
    $maxD = max(0.01, $porDiaSemana->max('total'));
    $claseMargen = fn ($m) => $m === null ? '' : ($m >= 40 ? 'bien' : ($m >= 20 ? 'medio' : 'mal'));
    $puedeVentas = auth()->user()->hasPermiso('ventas');
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-chart-line mr-2 text-secondary"></i>Reporte de ventas</h2>
        <p>Cuánto se vendió, qué se vende más, a qué hora y cuánto se gana · {{ $textoPeriodo }}</p>
    </div>
    <div class="toolbar-actions rp-acciones-exp">
        <a href="{{ route('reportes.ventas.pdf', $q) }}" target="_blank" class="btn btn-light"><i class="fas fa-file-pdf mr-1 text-danger"></i>PDF</a>
        <a href="{{ route('reportes.ventas.excel', $q) }}" class="btn btn-light"><i class="fas fa-file-excel mr-1 text-success"></i>Excel</a>
    </div>
</div>

@include('reportes._periodo', ['ruta' => 'reportes.ventas', 'extra' => ['vendedor' => $idVendedor]])

@if($vendedores->count() > 1)
<form method="GET" action="{{ route('reportes.ventas') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    @foreach(array_filter(['periodo' => $periodo ?: null, 'desde' => request('desde'), 'hasta' => request('hasta')]) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <label class="fb-label mb-0">Vendedor</label>
    <select name="vendedor" class="form-control" style="width:auto" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach($vendedores as $u)<option value="{{ $u->id }}" {{ $idVendedor == $u->id ? 'selected' : '' }}>{{ $u->nombre }}</option>@endforeach
    </select>
</form>
@endif

<div class="inv-hero" style="--acento:#1e8e5a">
    <div class="ih-icono"><i class="fas fa-coins"></i></div>
    <div>
        <small>Vendido (IGV incluido, sin anuladas)</small>
        <h3>S/ {{ number_format($tot['total'], 2) }}</h3>
        @if($anterior && $anterior['variacion'] !== null)
            <small style="text-transform:none;letter-spacing:0;opacity:.85"><i class="fas fa-arrow-{{ $anterior['variacion'] >= 0 ? 'up' : 'down' }} mr-1"></i>{{ abs($anterior['variacion']) }}% {{ $anterior['variacion'] >= 0 ? 'más' : 'menos' }} que el periodo anterior (S/ {{ number_format($anterior['total'], 2) }})</small>
        @endif
    </div>
    <div class="ih-datos">
        <div><b>{{ $tot['n'] }}</b><span>ventas · S/ {{ number_format($tot['ticket'], 2) }} c/u en promedio</span></div>
        <div><b>{{ $tot['unidades'] }}</b><span>productos vendidos</span></div>
        <div><b>{{ $tot['margen'] !== null ? 'S/ ' . number_format($tot['ganancia'], 2) : '—' }}</b><span>ganancia estimada{{ $tot['margen'] !== null ? ' · ' . $tot['margen'] . ' % de margen' : '' }}</span></div>
    </div>
</div>

<div class="inv-resumen">
    <span class="ir"><i class="fas fa-receipt text-muted"></i>Sin IGV: <b>S/ {{ number_format($tot['neto'], 2) }}</b></span>
    <span class="ir"><i class="fas fa-landmark text-muted"></i>IGV: <b>S/ {{ number_format($tot['igv'], 2) }}</b></span>
    @if($horaPico)<span class="ir"><i class="fas fa-clock" style="color:#d4880f"></i>Hora pico: <b>{{ sprintf('%02d:00 – %02d:00', $horaPico['hora'], $horaPico['hora'] + 1) }}</b></span>@endif
    @if($tot['anuladas'])<span class="ir"><i class="fas fa-ban text-danger"></i><b>{{ $tot['anuladas'] }}</b> anulada(s) · S/ {{ number_format($tot['monto_anulado'], 2) }}</span>@endif
</div>

@if($ventas->isEmpty())
    <div class="empty-state"><i class="fas fa-cash-register"></i><p>No hay ventas en este periodo.</p></div>
@else
<div class="inv-grid2 mb-3">
    <div class="inv-box">
        <h5><i class="fas fa-chart-bar"></i>Ventas por {{ ['dia' => 'día', 'semana' => 'semana', 'mes' => 'mes'][$agrupar] }}</h5>
        <div class="rc-tiempo">
            @foreach($porTiempo as $t)
                <div class="t" title="{{ $t['etiqueta'] }}: S/ {{ number_format($t['total'], 2) }} en {{ $t['n'] }} venta(s)">
                    <b>{{ number_format($t['total'], 0) }}</b>
                    <span style="height: {{ round($t['total'] / $maxT * 78) }}%"></span>
                    <em>{{ $t['etiqueta'] }}</em>
                </div>
            @endforeach
        </div>
    </div>
    <div class="inv-box">
        <h5><i class="fas fa-clock"></i>Por hora del día</h5>
        <div class="rc-tiempo">
            @foreach($porHora as $h)
                <div class="t {{ $horaPico && $h['hora'] === $horaPico['hora'] ? 'pico' : '' }}" title="{{ sprintf('%02d:00', $h['hora']) }}: S/ {{ number_format($h['total'], 2) }} en {{ $h['n'] }} venta(s)">
                    <b>{{ $h['total'] > 0 ? number_format($h['total'], 0) : '' }}</b>
                    <span style="height: {{ round($h['total'] / $maxH * 78) }}%"></span>
                    <em>{{ $h['hora'] }}h</em>
                </div>
            @endforeach
        </div>
    </div>
</div>

<p class="cp-titulo"><i class="fas fa-trophy"></i>Productos más vendidos</p>
<div class="rp-caja mb-3">
    <div class="rp-scroll" style="max-height:480px">
    <table class="rp-tabla">
        <thead><tr>
            <th>Producto</th><th class="num">Unidades</th><th class="num">Ingreso</th><th class="num rp-ocultar-movil">% ventas</th>
            <th class="num rp-ocultar-movil">Costo</th><th class="num">Ganancia</th><th class="num">Margen</th>
        </tr></thead>
        <tbody>
        @foreach($porProducto as $i => $p)
            <tr>
                <td><span class="rv-pos {{ $i < 3 ? 'oro' : '' }}">{{ $i + 1 }}</span><b>{{ $p['nombre'] }}</b><div class="small text-muted" style="margin-left:1.9rem">{{ $p['categoria'] }}</div></td>
                <td class="num">{{ $p['cantidad'] }}</td>
                <td class="num font-weight-bold">S/ {{ number_format($p['ingreso'], 2) }}</td>
                <td class="num rp-ocultar-movil">
                    <div class="d-flex align-items-center justify-content-end" style="gap:.4rem">
                        <small>{{ $tot['total'] > 0 ? round($p['ingreso'] / $tot['total'] * 100) : 0 }}%</small>
                        <div class="rp-barra rv-barra" style="width:60px"><span style="width: {{ $tot['total'] > 0 ? round($p['ingreso'] / $tot['total'] * 100) : 0 }}%"></span></div>
                    </div>
                </td>
                <td class="num rp-ocultar-movil text-muted">{{ $p['costo'] !== null ? 'S/ ' . number_format($p['costo'], 2) : '—' }}</td>
                <td class="num">{{ $p['ganancia'] !== null ? 'S/ ' . number_format($p['ganancia'], 2) : '—' }}</td>
                <td class="num">@if($p['margen'] !== null)<span class="rv-margen {{ $claseMargen($p['margen']) }}">{{ $p['margen'] }} %</span>@else<small class="text-muted">sin costo</small>@endif</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr>
            <td>{{ $porProducto->count() }} productos</td><td class="num">{{ $tot['unidades'] }}</td><td class="num">S/ {{ number_format($tot['total'], 2) }}</td>
            <td class="rp-ocultar-movil"></td><td class="num rp-ocultar-movil">S/ {{ number_format($tot['costo'], 2) }}</td>
            <td class="num">S/ {{ number_format($tot['ganancia'], 2) }}</td><td class="num">{{ $tot['margen'] !== null ? $tot['margen'] . ' %' : '' }}</td>
        </tr></tfoot>
    </table>
    </div>
</div>
<p class="small text-muted" style="margin-top:-.4rem">
    <i class="fas fa-info-circle mr-1"></i>Ganancia = ingreso sin IGV − costo de producción (el costo registrado en cada producto).
    @if($tot['sin_costo']) {{ $tot['sin_costo'] }} producto(s) no tienen costo registrado y no entran en la ganancia.@endif
</p>

<div class="inv-grid2 mb-3">
    <div class="inv-box">
        <h5><i class="fas fa-wallet"></i>Por medio de pago</h5>
        @foreach($porMedio as $m)
            <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center" style="gap:.6rem">
                    <span class="rv-medio"><span class="ic" style="background: {{ $m['color'] }}"><i class="fas {{ $m['icono'] }}"></i></span><span><b>{{ $m['texto'] }}</b> <small class="text-muted">· {{ $m['n'] }} venta{{ $m['n'] == 1 ? '' : 's' }}</small></span></span>
                    <b style="white-space:nowrap">S/ {{ number_format($m['total'], 2) }} <small class="text-muted">{{ $tot['total'] > 0 ? round($m['total'] / $tot['total'] * 100) : 0 }}%</small></b>
                </div>
                <div class="rp-barra rv-barra mt-1"><span style="width: {{ $tot['total'] > 0 ? round($m['total'] / $tot['total'] * 100) : 0 }}%; background: {{ $m['color'] }} !important"></span></div>
            </div>
        @endforeach
    </div>
    <div class="inv-box">
        <h5><i class="fas fa-calendar-week"></i>Por día de la semana</h5>
        @foreach($porDiaSemana as $d)
            <div class="d-flex align-items-center mb-2" style="gap:.6rem">
                <span style="width:78px;font-size:.85rem" class="font-weight-bold">{{ $d['texto'] }}</span>
                <div class="rp-barra rv-barra flex-grow-1"><span style="width: {{ round($d['total'] / $maxD * 100) }}%"></span></div>
                <span style="width:96px;text-align:right;font-size:.85rem;white-space:nowrap"><b>S/ {{ number_format($d['total'], 0) }}</b> <small class="text-muted">{{ $d['n'] }}</small></span>
            </div>
        @endforeach
    </div>
</div>

<div class="inv-grid2 mb-3">
    <div class="inv-box">
        <h5><i class="fas fa-users"></i>Mejores clientes</h5>
        <div class="rp-scroll"><table class="rp-tabla">
            <thead><tr><th>Cliente</th><th class="num">Compras</th><th class="num">Total</th></tr></thead>
            <tbody>
            @foreach($porCliente->take(10) as $c)
                <tr>
                    <td>@if($c['nombre'] !== 'Cliente General' && auth()->user()->hasModulo('clientes'))<a href="{{ route('clientes.show', $c['id']) }}" class="font-weight-bold">{{ $c['nombre'] }}</a>@else<b>{{ $c['nombre'] }}</b>@endif
                        <div class="small text-muted">última: {{ $c['ultima']->format('d/m/Y') }}</div></td>
                    <td class="num">{{ $c['n'] }}</td>
                    <td class="num font-weight-bold">S/ {{ number_format($c['total'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>
    <div class="inv-box">
        <h5><i class="fas fa-user-tie"></i>Por vendedor</h5>
        <div class="rp-scroll"><table class="rp-tabla">
            <thead><tr><th>Vendedor</th><th class="num">Ventas</th><th class="num">Total</th><th class="num">Anuladas</th></tr></thead>
            <tbody>
            @foreach($porVendedor as $v)
                <tr><td><b>{{ $v['nombre'] }}</b></td><td class="num">{{ $v['n'] }}</td><td class="num font-weight-bold">S/ {{ number_format($v['total'], 2) }}</td><td class="num {{ $v['anuladas'] ? 'text-danger' : 'text-muted' }}">{{ $v['anuladas'] }}</td></tr>
            @endforeach
            </tbody>
        </table></div>
        @if($porCategoria->count() > 1)
            <h5 class="mt-3"><i class="fas fa-tags"></i>Por categoría</h5>
            @foreach($porCategoria as $c)
                <div class="d-flex justify-content-between small mb-1"><span>{{ $c['nombre'] }} <span class="text-muted">· {{ $c['cantidad'] }} und</span></span><b>S/ {{ number_format($c['ingreso'], 2) }}</b></div>
            @endforeach
        @endif
    </div>
</div>

@if($puedeVentas)
    <a href="{{ route('ventas.index', array_filter(['desde' => $desde?->toDateString(), 'hasta' => $hasta->toDateString(), 'periodo' => ''])) }}" class="btn btn-light"><i class="fas fa-list mr-1"></i>Ver las {{ $tot['n'] }} ventas del periodo</a>
@endif
@endif
@endsection
