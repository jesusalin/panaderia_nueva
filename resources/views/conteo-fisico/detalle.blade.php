@extends('layouts.app')
@section('title', 'Detalle del Conteo')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li>
    <li class="breadcrumb-item"><a href="{{ route('conteo-fisico.index') }}">Conteo Físico</a></li>
    <li class="breadcrumb-item"><a href="{{ route('conteo-fisico.historial') }}">Historial</a></li>
    <li class="breadcrumb-item active">Detalle</li>
@endsection

@include('partials.inv-estilos')

@section('content')
@php
    $primero = $items->sortBy('id')->first();
    $per = $resumen['per'];
    $color = $per <= 10 ? '#1e8e5a' : ($per <= 30 ? '#d4880f' : '#c0392b');
@endphp

<div class="inv-hero" style="--acento: {{ $color }}">
    <div class="ih-icono"><i class="fas fa-clipboard-check"></i></div>
    <div>
        <small>{{ str_contains((string) $primero->observacion, 'control') ? 'Conteo de insumos de control' : 'Conteo físico' }} · {{ $primero->usuario->apodo ?? $primero->usuario->nombre ?? '—' }}</small>
        <h3>{{ $primero->created_at->locale('es')->translatedFormat('l d \d\e F, H:i') }}</h3>
    </div>
    <div class="ih-datos">
        <div><b style="font-size:1.8rem">{{ $per }} %</b><span>PER de este conteo</span></div>
        <div><b>{{ $resumen['con_diferencia'] }} / {{ $resumen['verificados'] }}</b><span>con diferencia / contados</span></div>
        <div><b>{{ $resumen['faltante'] > 0 ? '−S/ ' . number_format($resumen['faltante'], 2) : '—' }}</b><span>faltante{{ $resumen['sobrante'] > 0 ? ' · sobrante +S/ ' . number_format($resumen['sobrante'], 2) : '' }}</span></div>
    </div>
    <div class="ih-acciones">
        <a href="{{ route('conteo-fisico.index') }}" class="btn btn-light btn-sm"><i class="fas fa-plus mr-1"></i>Nuevo conteo</a>
        <a href="{{ route('conteo-fisico.historial') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-history mr-1"></i>Historial y tendencia</a>
    </div>
</div>

<div class="inv-resumen">
    <span class="ir"><i class="fas fa-calculator" style="color:#2980b9"></i>PER = {{ $resumen['con_diferencia'] }} ÷ {{ $resumen['verificados'] }} × 100 = <b>{{ $per }} %</b></span>
    @if($resumen['con_diferencia'])
        <span class="ir"><i class="fas fa-balance-scale" style="color:#a5680d"></i>El stock de los {{ $resumen['con_diferencia'] }} insumo(s) con diferencia ya se corrigió (quedó en el kardex)</span>
    @else
        <span class="ir"><i class="fas fa-check-circle" style="color:#1e8e5a"></i>Todo coincidió con el sistema</span>
    @endif
</div>

@if(trim(str_replace(['Conteo de insumos de control', 'Conteo físico', ' — '], '', (string) $primero->observacion)))
<div class="alert alert-light border">
    <i class="fas fa-comment-dots mr-1"></i>{{ $primero->observacion }}
</div>
@endif

<div class="list-rows">
    @foreach($items as $it)
        @php $iconClase = $it->diferencia == 0 ? 'success' : 'danger'; @endphp
        <div class="list-row">
            <div class="lr-icon {{ $iconClase }}"><i class="fas fa-wheat-awn"></i></div>

            <div class="lr-main">
                <div class="lr-title">{{ $it->materia->nombre }}</div>
                <div class="lr-subtitle">
                    @if($it->diferencia == 0) Coincide con el sistema
                    @else {{ $it->diferencia < 0 ? 'Faltó' : 'Sobró' }} · S/ {{ number_format(abs($it->diferencia) * ($it->materia->costo_unitario ?? 0), 2) }} · exactitud {{ $it->exactitud }}%
                    @endif
                </div>
            </div>

            <div class="lr-meta">
                <div class="lm-item">
                    <span class="lm-label">Sistema</span>
                    <span class="lm-value">{{ \App\Support\Cantidad::conUnidad($it->stock_sistema, $it->materia->unidad->abreviatura) }}</span>
                </div>
                <div class="lm-item">
                    <span class="lm-label">Físico</span>
                    <span class="lm-value">{{ \App\Support\Cantidad::conUnidad($it->stock_fisico, $it->materia->unidad->abreviatura) }}</span>
                </div>
                <div class="lm-item">
                    <span class="lm-label">Diferencia</span>
                    <span class="lm-value {{ $it->diferencia == 0 ? 'text-success' : 'text-danger' }}">
                        {{ \App\Support\Cantidad::conUnidad($it->diferencia, $it->materia->unidad->abreviatura, true) }}
                    </span>
                </div>
            </div>

            <div class="lr-side">
                @if($it->diferencia == 0)
                    <span class="badge-soft badge-soft-success">Coincide</span>
                @else
                    <span class="badge-soft badge-soft-danger">Stock corregido</span>
                @endif
            </div>
        </div>
    @endforeach
</div>

@endsection
