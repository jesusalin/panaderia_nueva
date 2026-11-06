@extends('layouts.app')
@section('title', 'Nuevo cliente')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('content')
<div class="pfm-hero" style="--cat:#1e8e5a">
    <div class="ph-foto"><i class="fas fa-user-plus"></i></div>
    <div><small>Nuevo cliente</small><h3>Bodega, colegio, restaurante o persona que nos compra</h3></div>
</div>
<form action="{{ route('clientes.store') }}" method="POST" id="formCliente">
    @csrf
    @include('clientes._form', ['cliente' => null])
</form>
@endsection
