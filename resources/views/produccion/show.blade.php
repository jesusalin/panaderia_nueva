@extends('layouts.app')
@section('title', 'Detalle de Producción')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('produccion.index') }}">Producción</a></li>
    <li class="breadcrumb-item active">#{{ $produccion->id }}</li>
@endsection

@section('content')
@include('produccion._detalle')
@endsection
