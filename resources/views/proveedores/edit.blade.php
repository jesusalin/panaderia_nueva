@extends('layouts.app')
@section('title', 'Editar proveedor')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
    <li class="breadcrumb-item"><a href="{{ route('proveedores.show', $proveedor) }}">{{ $proveedor->nombre }}</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
<div class="pfm-hero">
    <div class="ph-foto"><i class="fas fa-truck"></i></div>
    <div><small>Editar proveedor</small><h3>{{ $proveedor->nombre }}</h3></div>
    <div class="ph-datos">
        <div><b>{{ $proveedor->insumos_count }}</b><span>insumos</span></div>
        <div><b>{{ $proveedor->compras_count }}</b><span>compras</span></div>
    </div>
</div>
@php $volver = \App\Support\Volver::url(route('proveedores.index')); @endphp
<form action="{{ route('proveedores.update', $proveedor) }}" method="POST" id="formProveedor">
    @csrf @method('PUT')
    <input type="hidden" name="volver" value="{{ $volver }}">
    @include('proveedores._form')
</form>
@endsection
