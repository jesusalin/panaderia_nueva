@extends('layouts.app')
@section('title', 'Nuevo proveedor')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('content')
<div class="pfm-hero">
    <div class="ph-foto"><i class="fas fa-truck"></i></div>
    <div><small>Nuevo proveedor</small><h3>Empresa que nos vende insumos</h3></div>
</div>
<form action="{{ route('proveedores.store') }}" method="POST" id="formProveedor">
    @csrf
    @include('proveedores._form', ['proveedor' => null])
</form>
@endsection
