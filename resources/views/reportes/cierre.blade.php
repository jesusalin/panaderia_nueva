@extends('layouts.app')
@section('title', 'Cierre diario')
@section('breadcrumb')
    @if(auth()->user()->hasPermiso('reportes.resumen'))<li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>@endif
    <li class="breadcrumb-item active">Cierre diario</li>
@endsection

@include('partials.reportes-estilos')

@section('content')
@php
    $C = \App\Support\Cantidad::class;
    $esHoy = $dia->isToday();
    $q = array_filter(['fecha' => $dia->toDateString(), 'ver' => $filtro !== 'todos' ? $filtro : null, 'buscar' => request('buscar')]);
    $acento = $cierre ? ($cambio ? '#d4880f' : '#1e8e5a') : '#b5451b';
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-calendar-check mr-2 text-secondary"></i>Cierre diario de inventario</h2>
        <p>Todos los insumos con su saldo inicial, entradas, salidas, ajustes y saldo final del día</p>
    </div>
    <div class="toolbar-actions rp-acciones-exp">
        <a href="{{ route('reportes.cierre.pdf', $q) }}" target="_blank" class="btn btn-light"><i class="fas fa-file-pdf mr-1 text-danger"></i>PDF</a>
        <a href="{{ route('reportes.cierre.excel', $q) }}" class="btn btn-light"><i class="fas fa-file-excel mr-1 text-success"></i>Excel</a>
    </div>
</div>

<form method="GET" action="{{ route('reportes.cierre') }}" class="rp-dias">
    <a href="{{ route('reportes.cierre', ['fecha' => $dia->copy()->subDay()->toDateString()]) }}" class="btn btn-light" title="Día anterior" aria-label="Día anterior"><i class="fas fa-chevron-left"></i></a>
    <input type="date" name="fecha" class="form-control" value="{{ $dia->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()" aria-label="Día del cierre">
    @unless($esHoy)
        <a href="{{ route('reportes.cierre', ['fecha' => $dia->copy()->addDay()->toDateString()]) }}" class="btn btn-light" title="Día siguiente" aria-label="Día siguiente"><i class="fas fa-chevron-right"></i></a>
        <a href="{{ route('reportes.cierre') }}" class="btn btn-light">Hoy</a>
    @endunless
</form>

<div class="inv-hero" style="--acento: {{ $acento }}">
    <div class="ih-icono"><i class="fas {{ $cierre ? 'fa-lock' : 'fa-lock-open' }}"></i></div>
    <div>
        <small>{{ $esHoy ? 'Hoy · ' : '' }}{{ $cierre ? 'Día cerrado' : 'Día sin cerrar' }}</small>
        <h3>{{ ucfirst($dia->locale('es')->translatedFormat('l d \d\e F \d\e Y')) }}</h3>
        @if($cierre)
            <small style="text-transform:none;letter-spacing:0;opacity:.85">Cerrado por {{ $cierre->usuario->apodo ?? $cierre->usuario->nombre ?? '—' }} el {{ $cierre->updated_at->format('d/m H:i') }}</small>
        @endif
    </div>
    <div class="ih-datos">
        <div><b>S/ {{ number_format($totales['valor_final'], 2) }}</b><span>valor al cierre</span></div>
        <div><b>{{ $totales['con_movimiento'] }} / {{ $totales['insumos'] }}</b><span>insumos con movimiento</span></div>
        <div><b>{{ $totales['movimientos'] }}</b><span>movimientos del día</span></div>
    </div>
</div>

@if($cierre && $cambio)
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Después de cerrar se registraron movimientos de este día: los saldos cambiaron. <b>Vuelve a cerrar</b> para guardar los saldos correctos.</div>
@endif
@if($totales['no_cuadran'])
    <div class="alert alert-danger"><i class="fas fa-times-circle mr-1"></i><b>{{ $totales['no_cuadran'] }} insumo(s) no cuadran</b> (saldo inicial + entradas − salidas ± ajustes ≠ saldo final). <a href="{{ route('reportes.cierre', ['fecha' => $dia->toDateString(), 'ver' => 'descuadre']) }}" class="alert-link">Ver cuáles</a></div>
@endif

<div class="inv-resumen">
    <span class="ir"><span class="im-flecha sube mr-1"><i class="fas fa-arrow-up"></i></span>Entró: <b>S/ {{ number_format($totales['valor_entradas'], 2) }}</b></span>
    <span class="ir"><span class="im-flecha baja mr-1"><i class="fas fa-arrow-down"></i></span>Salió: <b>S/ {{ number_format($totales['valor_salidas'], 2) }}</b></span>
    <a href="{{ route('reportes.cierre', ['fecha' => $dia->toDateString(), 'ver' => 'bajo']) }}" class="ir {{ $filtro === 'bajo' ? 'activo' : '' }}"><i class="fas fa-exclamation-triangle" style="color:#e67e22"></i><b>{{ $totales['bajo_minimo'] }}</b> bajo el mínimo</a>
</div>

{{-- Registrar el cierre --}}
<form method="POST" action="{{ route('reportes.cierre.guardar') }}" class="filter-bar flex-wrap js-confirm" style="gap:.6rem"
      data-confirm-title="{{ $cierre ? '¿Volver a cerrar el día?' : '¿Cerrar el día?' }}"
      data-confirm="Se guardan los saldos de {{ $totales['insumos'] }} insumos del {{ $dia->format('d/m/Y') }} (valor S/ {{ number_format($totales['valor_final'], 2) }}).{{ $cierre ? ' Reemplaza el cierre anterior.' : '' }}">
    @csrf
    <input type="hidden" name="fecha" value="{{ $dia->toDateString() }}">
    <div class="flex-grow-1" style="min-width:220px">
        <input type="text" name="observacion" maxlength="300" class="form-control" value="{{ old('observacion', $cierre->observacion ?? '') }}" placeholder="Nota del cierre (opcional): ej. se recibió harina tarde">
    </div>
    <button class="btn {{ $cierre ? 'btn-outline-success' : 'btn-success' }}"><i class="fas fa-lock mr-1"></i>{{ $cierre ? 'Volver a cerrar el día' : 'Cerrar el día' }}</button>
</form>

<div class="d-flex flex-wrap align-items-center mb-2" style="gap:.5rem">
    <div class="inv-chips mb-0">
        @foreach(\App\Http\Controllers\CierreInventarioController::FILTROS as $clave => $texto)
            <a href="{{ route('reportes.cierre', array_filter(['fecha' => $dia->toDateString(), 'ver' => $clave !== 'todos' ? $clave : null, 'buscar' => request('buscar')])) }}" class="inv-chip {{ $filtro === $clave ? 'activo' : '' }}">{{ $texto }}</a>
        @endforeach
    </div>
    <form method="GET" action="{{ route('reportes.cierre') }}" class="ml-auto" style="min-width:240px">
        <input type="hidden" name="fecha" value="{{ $dia->toDateString() }}">
        @if($filtro !== 'todos')<input type="hidden" name="ver" value="{{ $filtro }}">@endif
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" name="buscar" class="form-control" placeholder="Buscar insumo…" value="{{ request('buscar') }}" data-sugerencias="insumos" autocomplete="off">
        </div>
    </form>
</div>

<div class="rp-caja mb-4">
    <div class="rp-scroll">
    <table class="rp-tabla">
        <thead><tr>
            <th>Insumo</th>
            <th class="num">Saldo inicial</th>
            <th class="num">Entradas</th>
            <th class="num">Salidas</th>
            <th class="num">Ajustes</th>
            <th class="num">Saldo final</th>
            <th class="num rp-ocultar-movil">Valor</th>
            <th></th>
        </tr></thead>
        <tbody>
        @forelse($vista as $f)
            <tr class="{{ $f['movimientos'] ? '' : 'quieto' }}">
                <td>
                    @if(auth()->user()->hasPermiso('reportes.kardex'))
                        <a href="{{ route('reportes.kardex', ['item' => 'materia-' . $f['id'], 'fecha_desde' => $dia->toDateString(), 'fecha_hasta' => $dia->toDateString()]) }}" class="font-weight-bold" title="Ver su kardex de este día">{{ $f['nombre'] }}</a>
                    @else <b>{{ $f['nombre'] }}</b> @endif
                    <div class="small text-muted">{{ $f['movimientos'] ? $f['movimientos'] . ' mov.' : 'sin movimiento' }}</div>
                </td>
                <td class="num">{{ $C::conUnidad($f['inicial'], $f['unidad']) }}</td>
                <td class="num {{ $f['entradas'] > 0 ? 'inv-mas' : '' }}">{{ $f['entradas'] > 0 ? '+' . $C::conUnidad($f['entradas'], $f['unidad']) : '—' }}</td>
                <td class="num {{ $f['salidas'] > 0 ? 'inv-menos' : '' }}">{{ $f['salidas'] > 0 ? '−' . $C::conUnidad($f['salidas'], $f['unidad']) : '—' }}</td>
                <td class="num {{ $f['ajuste'] != 0 ? 'inv-aj' : '' }}">{{ $f['ajuste'] != 0 ? $C::conUnidad($f['ajuste'], $f['unidad'], true) : '—' }}</td>
                <td class="num"><b>{{ $C::conUnidad($f['final'], $f['unidad']) }}</b></td>
                <td class="num rp-ocultar-movil">S/ {{ number_format($f['valor_final'], 2) }}</td>
                <td class="text-right">
                    @if(!$f['cuadra'])<span class="rp-chip mal" title="No cuadra">no cuadra</span>
                    @elseif($f['bajo'])<span class="rp-chip bajo">bajo mín.</span>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Ningún insumo en esta vista.</td></tr>
        @endforelse
        </tbody>
        <tfoot><tr>
            <td>{{ $vista->count() }} insumo{{ $vista->count() == 1 ? '' : 's' }}</td>
            <td colspan="5" class="num rp-ocultar-movil">Valor del almacén al cierre</td>
            <td class="num">S/ {{ number_format($vista->sum('valor_final'), 2) }}</td>
            <td></td>
        </tr></tfoot>
    </table>
    </div>
</div>

<p class="cp-titulo"><i class="fas fa-history"></i>Últimos 14 días</p>
<div class="rp-hist mb-3">
    @foreach($historial as $h)
        <a href="{{ route('reportes.cierre', ['fecha' => $h['dia']->toDateString()]) }}" class="{{ $h['dia']->isSameDay($dia) ? 'activo' : '' }}">
            <b>{{ $h['dia']->isToday() ? 'Hoy' : ($h['dia']->isYesterday() ? 'Ayer' : $h['dia']->locale('es')->translatedFormat('D d/m')) }}</b>
            @if($h['cierre'])<span class="ok"><i class="fas fa-lock mr-1"></i>Cerrado {{ $h['cierre']->updated_at->format('H:i') }}</span>
            @elseif($h['movimientos'])<span class="no"><i class="fas fa-lock-open mr-1"></i>Sin cerrar</span>
            @else<span class="nada">Sin movimientos</span>@endif
            <div class="text-muted">{{ $h['movimientos'] }} mov.</div>
        </a>
    @endforeach
</div>
@endsection
