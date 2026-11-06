@extends('layouts.app')
@section('title', 'Movimientos de Materia Prima')
@section('breadcrumb') <li class="breadcrumb-item active">Movimientos de Materia Prima</li> @endsection

@include('partials.inv-estilos')

@section('content')
@php
    $motivos = \App\Http\Controllers\MovimientosController::MOTIVOS;
    $filtros = request()->except(['page', 'periodo']);
@endphp
<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-exchange-alt mr-2 text-info"></i>Movimientos de Materia Prima</h2>
        <p>{{ $materia ? 'Todo lo que entró y salió de "' . $materia->nombre . '"' : 'Entradas, salidas y ajustes de los insumos' }}</p>
    </div>
    @if($materia)
    <div class="toolbar-actions">
        <a href="{{ route('materia-prima.show', $materia) }}" class="btn btn-outline-secondary"><i class="fas fa-wheat-awn mr-1"></i>Ficha del insumo</a>
        <a href="{{ route('movimientos.index') }}" class="btn btn-light"><i class="fas fa-times mr-1"></i>Ver todos</a>
    </div>
    @endif
</div>

<div class="inv-chips">
    @foreach(\App\Http\Controllers\MovimientosController::PERIODOS as $clave => $texto)
        <a href="{{ route('movimientos.index', $filtros + ['periodo' => $clave]) }}" class="inv-chip {{ $periodo === (string) $clave ? 'activo' : '' }}">{{ $texto }}</a>
    @endforeach
</div>

<div class="inv-resumen">
    <span class="ir"><span class="im-flecha sube mr-1"><i class="fas fa-arrow-up"></i></span><b>{{ $resumen['entrada']->n ?? 0 }}</b> entradas · S/ {{ number_format($resumen['entrada']->valor ?? 0, 2) }}</span>
    <span class="ir"><span class="im-flecha baja mr-1"><i class="fas fa-arrow-down"></i></span><b>{{ $resumen['salida']->n ?? 0 }}</b> salidas · S/ {{ number_format($resumen['salida']->valor ?? 0, 2) }}</span>
    <span class="ir"><span class="im-flecha ajuste mr-1"><i class="fas fa-exchange-alt fa-rotate-90"></i></span><b>{{ $resumen['ajuste']->n ?? 0 }}</b> ajustes</span>
    @foreach($motivos as $clave => $texto)
        @if(($porMotivo[$clave] ?? 0) > 0)
            <a href="{{ route('movimientos.index', array_merge(request()->except('page'), ['motivo' => $clave])) }}" class="ir {{ request('motivo') === $clave ? 'activo' : '' }}">{{ $texto }}: <b>{{ $porMotivo[$clave] }}</b></a>
        @endif
    @endforeach
</div>

<form method="GET" action="{{ route('movimientos.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    @if(request('id_materia'))<input type="hidden" name="id_materia" value="{{ request('id_materia') }}">@endif
    <input type="hidden" name="periodo" value="{{ $periodo }}">
    @unless($materia)
    <div class="search-box flex-grow-1" style="min-width:200px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" data-sugerencias="insumos" autocomplete="off" class="form-control" placeholder="Buscar insumo…" value="{{ request('buscar') }}">
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
        <option value="ajuste"  {{ request('tipo') === 'ajuste'  ? 'selected' : '' }}>Ajustes</option>
    </select>
    <details {{ !$periodo ? 'open' : '' }}>
        <summary class="small text-muted" style="cursor:pointer;">Fechas exactas</summary>
        <div class="d-flex mt-2" style="gap:.4rem;">
            <input type="date" name="desde" class="form-control" value="{{ request('desde') }}" onchange="this.form.periodo.value=''">
            <input type="date" name="hasta" class="form-control" value="{{ request('hasta') }}" onchange="this.form.periodo.value=''">
        </div>
    </details>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request()->hasAny(['buscar', 'motivo', 'tipo', 'desde', 'hasta']))
        <a href="{{ route('movimientos.index', array_filter(['id_materia' => request('id_materia'), 'periodo' => $periodo])) }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@include('partials.inv-movimientos', ['tipoItem' => 'materia', 'motivos' => $motivos])
@endsection
