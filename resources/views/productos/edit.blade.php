@extends('layouts.app')
@section('title', 'Editar Producto')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
    <li class="breadcrumb-item"><a href="{{ route('productos.show', $producto) }}">{{ $producto->nombre }}</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@include('productos.partials._styles')

@section('content')
<div class="pfm-hero" style="--cat: {{ $producto->categoria->color_final ?? '#b5451b' }}">
    <div class="ph-foto">
        @if($producto->imagen)<img src="{{ asset('storage/' . $producto->imagen) }}" alt="">@else<i class="fas {{ $producto->categoria->icono_final ?? 'fa-bread-slice' }}"></i>@endif
    </div>
    <div>
        <small>Editar producto · {{ $producto->categoria->nombre ?? 'Sin categoría' }}</small>
        <h3>{{ $producto->nombre }}</h3>
    </div>
    <div class="ph-datos">
        <div><b>{{ $producto->stock_actual }}</b><span>en stock</span></div>
        <div><b>{{ $vendidos30 }}</b><span>vendidos (30 días)</span></div>
        <div><b>{{ $producto->margen !== null ? $producto->margen . '%' : '—' }}</b><span>ganancia</span></div>
    </div>
</div>

@php $volver = \App\Support\Volver::url(route('productos.index')); @endphp
<form action="{{ route('productos.update', $producto) }}" method="POST" enctype="multipart/form-data" id="formProducto">
    <input type="hidden" name="volver" value="{{ $volver }}">
    @csrf @method('PUT')
    @include('productos.partials._form')
</form>
@endsection
