@extends('layouts.app')
@section('title', 'Editar usuario')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item"><a href="{{ route('usuarios.show', $usuario) }}">{{ $usuario->nombre }}</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@include('usuarios.partials._styles')

@section('content')
@php $act = $usuario->estadoActividad(); @endphp
<div class="pfm-hero">
    <div class="ph-foto">@if($usuario->foto_url)<img src="{{ $usuario->foto_url }}" alt="">@else<span style="font-weight:800">{{ $usuario->iniciales }}</span>@endif</div>
    <div><small>Editar usuario · {{ $usuario->isAdmin() ? 'Administrador' : 'Usuario' }}{{ auth()->id() === $usuario->id ? ' · eres tú' : '' }}</small><h3>{{ $usuario->nombre }}</h3></div>
    <div class="ph-datos">
        <div><b>{{ $usuario->usuario }}</b><span>usuario</span></div>
        <div><b>{{ $act['clave'] === 'en_linea' ? 'En línea' : ($usuario->ultimo_acceso ? $usuario->ultimo_acceso->format('d/m H:i') : 'Nunca') }}</b><span>último acceso</span></div>
    </div>
</div>
@php $volver = \App\Support\Volver::url(route('usuarios.index')); @endphp
<form action="{{ route('usuarios.update', $usuario) }}" method="POST" autocomplete="off">
    @csrf @method('PUT')
    <input type="hidden" name="volver" value="{{ $volver }}">
    @include('usuarios.partials._form')
</form>
@if(auth()->id() !== $usuario->id)
<form id="formClaveTemporal" action="{{ route('usuarios.clave-temporal', $usuario) }}" method="POST" class="d-none js-confirm"
      data-confirm-title="¿Darle una contraseña nueva?"
      data-confirm="La contraseña actual de &quot;{{ $usuario->nombre }}&quot; dejará de funcionar. Te mostraremos la nueva para que se la des.">
    @csrf @method('PUT')
</form>
@endif
@endsection
