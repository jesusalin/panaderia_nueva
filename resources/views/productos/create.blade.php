@extends('layouts.app')
@section('title', 'Nuevo Producto')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@include('productos.partials._styles')

@section('content')
<div class="pfm-hero">
    <div class="ph-foto"><i class="fas fa-plus"></i></div>
    <div>
        <small>Catálogo</small>
        <h3>Nuevo producto</h3>
    </div>
</div>

<form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data" id="formProducto">
    @csrf
    @include('productos.partials._form', ['producto' => null])
</form>
@endsection
