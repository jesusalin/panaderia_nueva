@extends('layouts.app')
@section('title', 'Nuevo usuario')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@include('usuarios.partials._styles')

@section('content')
<div class="pfm-hero">
    <div class="ph-foto"><i class="fas fa-user-plus"></i></div>
    <div><small>Nuevo usuario</small><h3>Cuenta para una persona que usará el sistema</h3></div>
</div>
<form action="{{ route('usuarios.store') }}" method="POST" autocomplete="off">
    @csrf
    @include('usuarios.partials._form', ['usuario' => null])
</form>
@endsection
