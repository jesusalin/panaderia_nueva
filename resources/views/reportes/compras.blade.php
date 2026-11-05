@extends('layouts.app')
@section('title', 'Reporte de compras')
@section('breadcrumb')
    @if(auth()->user()->hasPermiso('reportes.resumen'))<li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>@endif
    <li class="breadcrumb-item active">Compras</li>
@endsection

@include('partials.reportes-estilos')

@push('styles')
<style>
    .rc-tiempo { display: flex; align-items: flex-end; gap: 6px; height: 150px; padding-top: 1rem; overflow-x: auto; }
    .rc-tiempo .t { flex: 1 0 34px; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .rc-tiempo .t span { width: 100%; max-width: 44px; background: linear-gradient(180deg, #d9653a, #b5451b); border-radius: 5px 5px 0 0; min-height: 2px; }
    .rc-tiempo .t em { font-style: normal; font-size: .64rem; color: #8a8a9d; margin-top: .25rem; white-space: nowrap; }
    .rc-tiempo .t b { font-size: .6rem; color: #6c757d; white-space: nowrap; margin-bottom: .15rem; }
</style>
@endpush

@section('content')
@php
    $C = \App\Support\Cantidad::class;
    $q = array_filter(['periodo' => $periodo ?: null, 'desde' => request('desde'), 'hasta' => request('hasta'), 'proveedor' => $idProveedor]);
    $maxT = max(0.01, $porTiempo->max('total'));
    $puedeCompras = auth()->user()->hasPermiso('compras.compras');
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-shopping-basket mr-2 text-secondary"></i>Reporte de compras</h2>
        <p>Cuánto se gastó, a quién y cómo cambiaron los precios · {{ $textoPeriodo }}</p>
    </div>
    <div class="toolbar-actions rp-acciones-exp">
        <a href="{{ route('reportes.compras.pdf', $q) }}" target="_blank" class="btn btn-light"><i class="fas fa-file-pdf mr-1 text-danger"></i>PDF</a>
        <a href="{{ route('reportes.compras.excel', $q) }}" class="btn btn-light"><i class="fas fa-file-excel mr-1 text-success"></i>Excel</a>
    </div>
</div>

@include('reportes._periodo', ['ruta' => 'reportes.compras', 'extra' => ['proveedor' => $idProveedor]])

<form method="GET" action="{{ route('reportes.compras') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    @foreach(array_filter(['periodo' => $periodo ?: null, 'desde' => request('desde'), 'hasta' => request('hasta')]) as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
    <label class="fb-label mb-0">Proveedor</label>
    <select name="proveedor" class="form-control" style="width:auto" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach($proveedores as $p)<option value="{{ $p->id }}" {{ $idProveedor == $p->id ? 'selected' : '' }}>{{ $p->nombre }}</option>@endforeach
    </select>
</form>

<div class="inv-hero" style="--acento:#d4880f">
    <div class="ih-icono"><i class="fas fa-shopping-basket"></i></div>
    <div><small>Gastado (con IGV, sin anuladas)</small><h3>S/ {{ number_format($tot['total'], 2) }}</h3></div>
    <div class="ih-datos">
        <div><b>{{ $tot['n'] }}</b><span>compras · S/ {{ number_format($tot['promedio'], 2) }} c/u en promedio</span></div>
        <div><b>S/ {{ number_format($tot['igv'], 2) }}</b><span>de IGV</span></div>
        <div><b>S/ {{ number_format($tot['pendiente'], 2) }}</b><span>{{ $tot['pendientes'] }} por recibir{{ $tot['anuladas'] ? ' · ' . $tot['anuladas'] . ' anulada(s)' : '' }}</span></div>
    </div>
</div>

@if($compras->isEmpty())
    <div class="empty-state"><i class="fas fa-shopping-basket"></i><p>No hay compras en este periodo.</p></div>
@else
<div class="inv-grid2 mb-3">
    <div class="inv-box">
        <h5><i class="fas fa-chart-bar"></i>Gasto por {{ ['dia' => 'día', 'semana' => 'semana', 'mes' => 'mes'][$agrupar] }}</h5>
        <div class="rc-tiempo">
            @foreach($porTiempo as $t)
                <div class="t" title="{{ $t['etiqueta'] }}: S/ {{ number_format($t['total'], 2) }} en {{ $t['n'] }} compra(s)">
                    <b>{{ number_format($t['total'], 0) }}</b>
                    <span style="height: {{ round($t['total'] / $maxT * 78) }}%"></span>
                    <em>{{ $t['etiqueta'] }}</em>
                </div>
            @endforeach
        </div>
    </div>
    <div class="inv-box">
        <h5><i class="fas fa-truck"></i>Por proveedor</h5>
        @foreach($porProveedor as $p)
            <div class="mb-2">
                <div class="d-flex justify-content-between" style="gap:.6rem">
                    <span>@if(auth()->user()->hasPermiso('compras.proveedores'))<a href="{{ route('proveedores.show', $p['id']) }}" class="font-weight-bold">{{ $p['nombre'] }}</a>@else<b>{{ $p['nombre'] }}</b>@endif
                        <small class="text-muted">· {{ $p['n'] }} compra{{ $p['n'] == 1 ? '' : 's' }}</small></span>
                    <b style="white-space:nowrap">S/ {{ number_format($p['total'], 2) }} <small class="text-muted">{{ $tot['total'] > 0 ? round($p['total'] / $tot['total'] * 100) : 0 }}%</small></b>
                </div>
                <div class="rp-barra mt-1"><span style="width: {{ $tot['total'] > 0 ? round($p['total'] / $tot['total'] * 100) : 0 }}%"></span></div>
            </div>
        @endforeach
    </div>
</div>

<p class="cp-titulo"><i class="fas fa-wheat-awn"></i>Por insumo — precios sin IGV</p>
<div class="rp-caja mb-3">
    <div class="rp-scroll">
    <table class="rp-tabla">
        <thead><tr>
            <th>Insumo</th><th class="num">Comprado</th><th class="num">Gasto</th><th class="num">Precio prom.</th>
            <th class="num rp-ocultar-movil">Mín. – máx.</th><th class="num">Primero → último</th><th class="num">Variación</th>
        </tr></thead>
        <tbody>
        @foreach($porInsumo as $i)
            <tr>
                <td><b>{{ $i['nombre'] }}</b><div class="small text-muted">{{ $i['veces'] }} compra{{ $i['veces'] == 1 ? '' : 's' }} · {{ $i['proveedores']->join(', ') }}</div></td>
                <td class="num">{{ $C::conUnidad($i['cantidad'], $i['unidad']) }}</td>
                <td class="num font-weight-bold">S/ {{ number_format($i['gasto'], 2) }}</td>
                <td class="num">S/ {{ number_format($i['promedio'], 2) }}<small class="text-muted">/{{ $i['unidad'] }}</small></td>
                <td class="num rp-ocultar-movil text-muted">{{ number_format($i['min'], 2) }} – {{ number_format($i['max'], 2) }}</td>
                <td class="num">{{ number_format($i['primero'], 2) }} → {{ number_format($i['ultimo'], 2) }}</td>
                <td class="num">
                    @if($i['variacion'] === null)<span class="text-muted">—</span>
                    @elseif($i['variacion'] > 0)<span class="cp-var sube">▲ {{ $i['variacion'] }}%</span>
                    @elseif($i['variacion'] < 0)<span class="cp-var baja">▼ {{ abs($i['variacion']) }}%</span>
                    @else<span class="cp-var igual">igual</span>@endif
                </td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><td>{{ $porInsumo->count() }} insumos</td><td></td><td class="num">S/ {{ number_format($porInsumo->sum('gasto'), 2) }}</td><td colspan="4" class="text-muted font-weight-normal">sin IGV</td></tr></tfoot>
    </table>
    </div>
</div>

<p class="cp-titulo"><i class="fas fa-receipt"></i>Compras del periodo ({{ $compras->count() }})</p>
<div class="rp-caja mb-3">
    <div class="rp-scroll" style="max-height:420px">
    <table class="rp-tabla">
        <thead><tr><th>Compra</th><th>Fecha</th><th>Proveedor</th><th class="rp-ocultar-movil">Insumos</th><th class="num">Total</th><th>Estado</th></tr></thead>
        <tbody>
        @foreach($compras->sortByDesc('fecha_compra') as $c)
            @php $est = $c->estado === 'pendiente' && $c->atrasada ? 'atrasada' : $c->estado; @endphp
            <tr>
                <td>@if($puedeCompras)<a href="{{ route('compras.show', $c) }}" class="font-weight-bold">{{ $c->codigo }}</a>@else<b>{{ $c->codigo }}</b>@endif</td>
                <td>{{ $c->fecha_compra->format('d/m/Y') }}</td>
                <td>{{ $c->proveedor->nombre ?? '—' }}</td>
                <td class="small text-muted rp-ocultar-movil">{{ \Illuminate\Support\Str::limit($c->detalles->map(fn ($d) => $d->materia->nombre ?? '')->join(', '), 50) }}</td>
                <td class="num font-weight-bold">S/ {{ number_format($c->total, 2) }}</td>
                <td><span class="cp-estado {{ $est }}">{{ ['pendiente' => 'Por recibir', 'atrasada' => 'Atrasada', 'recibida' => 'Recibida'][$est] ?? $est }}</span></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif
@endsection
