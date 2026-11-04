@extends('layouts.app')
@section('title', 'Movimientos de Productos')
@section('breadcrumb') <li class="breadcrumb-item active">Movimientos de Productos</li> @endsection

@include('partials.inv-estilos')

@section('content')
@php
    $motivos = \App\Http\Controllers\KardexController::MOTIVOS;
    $filtros = request()->except(['page', 'periodo']);
@endphp
<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-exchange-alt mr-2 text-info"></i>Movimientos de Productos</h2>
        <p>{{ $producto ? 'Todo lo que entró y salió de "' . $producto->nombre . '"' : 'Producción, ventas, ajustes y devoluciones de los productos' }}</p>
    </div>
    @if($producto)
    <div class="toolbar-actions">
        <a href="{{ route('productos.show', $producto) }}" class="btn btn-outline-secondary"><i class="fas fa-bread-slice mr-1"></i>Ficha del producto</a>
        <a href="{{ route('kardex.index') }}" class="btn btn-light"><i class="fas fa-times mr-1"></i>Ver todos</a>
    </div>
    @endif
</div>

<div class="inv-chips">
    @foreach(\App\Http\Controllers\MovimientosController::PERIODOS as $clave => $texto)
        <a href="{{ route('kardex.index', $filtros + ['periodo' => $clave]) }}" class="inv-chip {{ $periodo === (string) $clave ? 'activo' : '' }}">{{ $texto }}</a>
    @endforeach
</div>

<div class="inv-resumen">
    <span class="ir"><span class="im-flecha sube mr-1"><i class="fas fa-arrow-up"></i></span>Entraron <b>{{ number_format($resumen['entrada']->unidades ?? 0) }}</b> und. <small>({{ $resumen['entrada']->n ?? 0 }} mov.)</small></span>
    <span class="ir"><span class="im-flecha baja mr-1"><i class="fas fa-arrow-down"></i></span>Salieron <b>{{ number_format($resumen['salida']->unidades ?? 0) }}</b> und. <small>({{ $resumen['salida']->n ?? 0 }} mov. · S/ {{ number_format($resumen['salida']->valor ?? 0, 2) }} a precio de venta)</small></span>
    @foreach($motivos as $clave => $texto)
        @if(($porMotivo[$clave] ?? 0) > 0)
            <a href="{{ route('kardex.index', request()->except('page') + ['motivo' => $clave]) }}" class="ir {{ request('motivo') === $clave ? 'activo' : '' }}">{{ $texto }}: <b>{{ $porMotivo[$clave] }}</b></a>
        @endif
    @endforeach
</div>

<form method="GET" action="{{ route('kardex.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    @if(request('id_producto'))<input type="hidden" name="id_producto" value="{{ request('id_producto') }}">@endif
    <input type="hidden" name="periodo" value="{{ $periodo }}">
    @unless($producto)
    <div class="search-box flex-grow-1" style="min-width:200px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" data-sugerencias="productos" autocomplete="off" class="form-control" placeholder="Buscar producto…" value="{{ request('buscar') }}">
    </div>
    @endunless
    <label class="fb-label mb-0">Motivo</label>
    <select name="motivo" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach($motivos as $clave => $texto)<option value="{{ $clave }}" {{ request('motivo') === $clave ? 'selected' : '' }}>{{ $texto }}</option>@endforeach
    </select>
    <label class="fb-label mb-0">Tipo</label>
    <select name="tipo" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="entrada" {{ request('tipo') === 'entrada' ? 'selected' : '' }}>Entradas</option>
        <option value="salida"  {{ request('tipo') === 'salida'  ? 'selected' : '' }}>Salidas</option>
    </select>
    <details {{ !$periodo ? 'open' : '' }}>
        <summary class="small text-muted" style="cursor:pointer;">Fechas exactas</summary>
        <div class="d-flex mt-2" style="gap:.4rem;">
            <input type="date" name="desde" class="form-control" value="{{ $desde }}" onchange="this.form.periodo.value=''">
            <input type="date" name="hasta" class="form-control" value="{{ $hasta }}" onchange="this.form.periodo.value=''">
        </div>
    </details>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request()->hasAny(['buscar', 'motivo', 'tipo', 'desde', 'hasta']))
        <a href="{{ route('kardex.index', array_filter(['id_producto' => request('id_producto'), 'periodo' => $periodo])) }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@include('partials.inv-movimientos', ['tipoItem' => 'producto', 'motivos' => $motivos])
@endsection
