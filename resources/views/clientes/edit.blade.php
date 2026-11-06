@extends('layouts.app')
@section('title', 'Editar cliente')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
    <li class="breadcrumb-item"><a href="{{ route('clientes.show', $cliente) }}">{{ $cliente->nombre }}</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
<div class="pfm-hero" style="--cat: {{ $cliente->color_tipo }}">
    <div class="ph-foto"><i class="fas {{ $cliente->icono }}"></i></div>
    <div><small>Editar cliente · {{ $cliente->tipo_label }}</small><h3>{{ $cliente->nombre }}</h3></div>
    <div class="ph-datos">
        <div><b>{{ $cliente->compras_count }}</b><span>compras</span></div>
        <div><b>{{ $cliente->created_at?->format('d/m/Y') ?? '—' }}</b><span>cliente desde</span></div>
    </div>
</div>
@php $volver = \App\Support\Volver::url(route('clientes.index')); @endphp
<form action="{{ route('clientes.update', $cliente) }}" method="POST" id="formCliente">
    @csrf @method('PUT')
    <input type="hidden" name="volver" value="{{ $volver }}">
    @include('clientes._form')
</form>
@endsection
