@extends('layouts.app')
@section('title', 'Compra #' . $compra->id)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>
    <li class="breadcrumb-item active">Compra #{{ $compra->id }}</li>
@endsection

@section('content')
@include('compras._detalle')
@endsection
