@extends('layouts.app')
@section('title', 'Reporte de mermas')
@section('breadcrumb')
    @if(auth()->user()->hasPermiso('reportes.resumen'))<li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>@endif
    <li class="breadcrumb-item active">Mermas</li>
@endsection

@include('partials.reportes-estilos')

@section('content')
@php
    $C = \App\Support\Cantidad::class;
    $q = array_filter(['periodo' => $periodo ?: null, 'desde' => request('desde'), 'hasta' => request('hasta'), 'tipo' => $tipo !== 'todos' ? $tipo : null]);
    $maxItem = max(0.01, $porItem->max('valor'));
    $iconos = ['vencido' => 'fa-calendar-times', 'roto' => 'fa-heart-broken', 'malestado' => 'fa-bug', 'otro' => 'fa-question'];
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-trash-alt mr-2 text-secondary"></i>Reporte de mermas</h2>
        <p>Lo que se perdió (vencido, roto o en mal estado) y cuánto costó · {{ $textoPeriodo }}</p>
    </div>
    <div class="toolbar-actions rp-acciones-exp">
        <a href="{{ route('reportes.mermas.pdf', $q) }}" target="_blank" class="btn btn-light"><i class="fas fa-file-pdf mr-1 text-danger"></i>PDF</a>
        <a href="{{ route('reportes.mermas.excel', $q) }}" class="btn btn-light"><i class="fas fa-file-excel mr-1 text-success"></i>Excel</a>
    </div>
</div>

@include('reportes._periodo', ['ruta' => 'reportes.mermas', 'extra' => ['tipo' => $tipo !== 'todos' ? $tipo : null]])

<div class="inv-hero" style="--acento:#c0392b">
    <div class="ih-icono"><i class="fas fa-trash-alt"></i></div>
    <div><small>Pérdida por mermas</small><h3>S/ {{ number_format($total, 2) }}</h3></div>
    <div class="ih-datos">
        <div><b>{{ $filas->count() }}</b><span>registros de merma</span></div>
        <div><b>{{ $porItem->count() }}</b><span>insumos / productos</span></div>
        <div title="Lo perdido de insumos sobre todo lo que salió (producción + merma), en soles"><b>{{ $indice !== null ? $indice . ' %' : '—' }}</b><span>índice de merma de insumos</span></div>
    </div>
</div>

<div class="inv-chips">
    @foreach(['todos' => 'Insumos y productos', 'insumos' => 'Solo insumos', 'productos' => 'Solo productos'] as $clave => $texto)
        <a href="{{ route('reportes.mermas', array_filter(['periodo' => $periodo ?: null, 'desde' => request('desde'), 'hasta' => request('hasta'), 'tipo' => $clave !== 'todos' ? $clave : null])) }}" class="inv-chip {{ $tipo === $clave ? 'activo' : '' }}">{{ $texto }}</a>
    @endforeach
</div>

@if($filas->isEmpty())
    <div class="empty-state"><i class="fas fa-check-circle" style="color:#1e8e5a;opacity:1"></i><p><b>Sin mermas</b> en este periodo.</p></div>
@else
<div class="inv-grid2 mb-3">
    <div class="inv-box">
        <h5><i class="fas fa-list-ol"></i>Lo que más se pierde</h5>
        <table class="inv-tabla">
            <thead><tr><th>Insumo / producto</th><th class="text-right">Cantidad</th><th style="width:28%"></th><th class="text-right">S/</th></tr></thead>
            <tbody>
            @foreach($porItem->take(10) as $i)
                <tr>
                    <td><b>{{ $i['nombre'] }}</b><div class="small text-muted">{{ $i['tipo'] === 'insumo' ? 'Insumo' : 'Producto' }} · {{ $i['veces'] }} vez{{ $i['veces'] == 1 ? '' : 'es' }} · {{ \App\Http\Controllers\ReportesGestionController::MOTIVOS[$i['motivo']] }}</div></td>
                    <td class="text-right">{{ $i['tipo'] === 'insumo' ? $C::conUnidad($i['cantidad'], $i['unidad']) : (int) $i['cantidad'] . ' und' }}</td>
                    <td><div class="rp-barra"><span style="width: {{ round($i['valor'] / $maxItem * 100) }}%"></span></div></td>
                    <td class="text-right font-weight-bold">{{ number_format($i['valor'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="inv-box">
        <h5><i class="fas fa-tags"></i>Por motivo</h5>
        @foreach($porMotivo as $clave => $m)
            <div class="d-flex align-items-center mb-3" style="gap:.7rem">
                <span class="im-flecha baja" style="width:34px;height:34px;font-size:.85rem"><i class="fas {{ $iconos[$clave] }}"></i></span>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between"><b>{{ $m['texto'] }}</b><b>S/ {{ number_format($m['valor'], 2) }}</b></div>
                    <div class="rp-barra mt-1"><span style="width: {{ $total > 0 ? round($m['valor'] / $total * 100) : 0 }}%"></span></div>
                    <small class="text-muted">{{ $m['veces'] }} registro{{ $m['veces'] == 1 ? '' : 's' }} · {{ $total > 0 ? round($m['valor'] / $total * 100) : 0 }}% de lo perdido</small>
                </div>
            </div>
        @endforeach
        <p class="small text-muted mb-0 mt-2"><i class="fas fa-info-circle mr-1"></i>Valorizado al costo actual de cada insumo (promedio ponderado) y al costo de producción de cada producto.</p>
    </div>
</div>

<p class="cp-titulo"><i class="fas fa-stream"></i>Detalle ({{ $filas->count() }})</p>
<div class="rp-caja mb-3">
    <div class="rp-scroll">
    <table class="rp-tabla">
        <thead><tr><th>Fecha</th><th>Insumo / producto</th><th class="num">Cantidad</th><th class="num">S/</th><th>Motivo</th><th class="rp-ocultar-movil">Detalle</th><th class="rp-ocultar-movil">Registró</th></tr></thead>
        <tbody>
        @foreach($filas as $f)
            <tr>
                <td class="text-muted" style="white-space:nowrap">{{ $f['fecha']->format('d/m/Y H:i') }}</td>
                <td><b>{{ $f['nombre'] }}</b> <span class="rp-chip gris">{{ $f['tipo'] }}</span></td>
                <td class="num">{{ $f['texto'] }}</td>
                <td class="num inv-menos">{{ number_format($f['valor'], 2) }}</td>
                <td>{{ \App\Http\Controllers\ReportesGestionController::MOTIVOS[$f['motivo']] }}</td>
                <td class="small text-muted rp-ocultar-movil">{{ $f['detalle'] }}</td>
                <td class="small rp-ocultar-movil">{{ $f['usuario'] }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><td colspan="3">Total</td><td class="num">S/ {{ number_format($total, 2) }}</td><td colspan="3"></td></tr></tfoot>
    </table>
    </div>
</div>
@endif
@endsection
