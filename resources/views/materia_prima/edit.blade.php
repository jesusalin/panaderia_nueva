@extends('layouts.app')
@section('title', 'Editar insumo')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li>
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.show', $materiaPrima) }}">{{ $materiaPrima->nombre }}</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@include('materia_prima.partials._styles')

@section('content')
@php $u = $materiaPrima->unidad->abreviatura ?? ''; @endphp
<div class="pfm-hero">
    <div class="ph-foto"><i class="fas fa-wheat-awn"></i></div>
    <div><small>Editar insumo · {{ $materiaPrima->proveedor->nombre ?? 'sin proveedor' }}</small><h3>{{ $materiaPrima->nombre }}</h3></div>
    <div class="ph-datos">
        <div><b>{{ \App\Support\Cantidad::conUnidad($materiaPrima->stock_actual, $u) }}</b><span>en almacén</span></div>
        <div><b>S/ {{ number_format($materiaPrima->costo_unitario, 2) }}</b><span>por {{ $u }}</span></div>
        <div><b>S/ {{ number_format($materiaPrima->valor, 2) }}</b><span>valor</span></div>
    </div>
</div>
@php $volver = \App\Support\Volver::url(route('materia-prima.index')); @endphp
<form action="{{ route('materia-prima.update', $materiaPrima) }}" method="POST">
    <input type="hidden" name="volver" value="{{ $volver }}">
    @csrf @method('PUT')
    @include('materia_prima.partials._form')
</form>
@endsection
