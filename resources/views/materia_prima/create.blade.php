@extends('layouts.app')
@section('title', 'Nuevo insumo')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@include('materia_prima.partials._styles')

@section('content')
<div class="pfm-hero">
    <div class="ph-foto"><i class="fas fa-plus"></i></div>
    <div><small>Inventario</small><h3>Nuevo insumo</h3></div>
</div>
<form action="{{ route('materia-prima.store') }}" method="POST">
    @csrf
    @include('materia_prima.partials._form', ['materiaPrima' => null])
</form>
@endsection
