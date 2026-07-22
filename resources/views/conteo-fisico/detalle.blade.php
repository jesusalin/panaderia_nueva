@extends('layouts.app')
@section('title', 'Detalle del Conteo')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('conteo-fisico.index') }}">Conteo Físico</a></li>
    <li class="breadcrumb-item"><a href="{{ route('conteo-fisico.historial') }}">Historial</a></li>
    <li class="breadcrumb-item active">Detalle</li>
@endsection

@section('content')

@php
    $exactitudPromedio = round($items->avg('exactitud'), 1);
    $clase = $exactitudPromedio >= 90 ? 'success' : ($exactitudPromedio >= 70 ? 'warning' : 'danger');
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-clipboard-list mr-2 text-info"></i>Conteo del {{ $items->first()->created_at->locale('es')->translatedFormat('d \d\e F, Y') }}</h2>
        <p>
            Por {{ $items->first()->usuario->apodo ?? $items->first()->usuario->nombre }}
            &middot; Exactitud promedio:
            <span class="badge badge-{{ $clase }}">{{ $exactitudPromedio }}%</span>
        </p>
    </div>
</div>

@if($items->first()->observacion)
<div class="alert alert-light border">
    <i class="fas fa-comment-dots mr-1"></i>{{ $items->first()->observacion }}
</div>
@endif

<div class="list-rows">
    @foreach($items as $it)
        @php $iconClase = $it->diferencia == 0 ? 'success' : 'danger'; @endphp
        <div class="list-row">
            <div class="lr-icon {{ $iconClase }}"><i class="fas fa-wheat-awn"></i></div>

            <div class="lr-main">
                <div class="lr-title">{{ $it->materia->nombre }}</div>
                <div class="lr-subtitle">Exactitud de este ítem: {{ $it->exactitud }}%</div>
            </div>

            <div class="lr-meta">
                <div class="lm-item">
                    <span class="lm-label">Sistema</span>
                    <span class="lm-value">{{ number_format($it->stock_sistema, 2) }} {{ $it->materia->unidad->abreviatura }}</span>
                </div>
                <div class="lm-item">
                    <span class="lm-label">Físico</span>
                    <span class="lm-value">{{ number_format($it->stock_fisico, 2) }} {{ $it->materia->unidad->abreviatura }}</span>
                </div>
                <div class="lm-item">
                    <span class="lm-label">Diferencia</span>
                    <span class="lm-value {{ $it->diferencia == 0 ? 'text-success' : 'text-danger' }}">
                        {{ $it->diferencia > 0 ? '+' : '' }}{{ number_format($it->diferencia, 2) }}
                    </span>
                </div>
            </div>

            <div class="lr-side">
                @if($it->diferencia == 0)
                    <span class="badge-soft badge-soft-success">Coincide</span>
                @else
                    <span class="badge-soft badge-soft-danger">Ajustado</span>
                @endif
            </div>
        </div>
    @endforeach
</div>

@endsection
