@extends('layouts.app')
@section('title', 'Venta ' . $venta->numero_venta)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ventas.index') }}">Ventas</a></li>
    <li class="breadcrumb-item active">{{ $venta->numero_venta }}</li>
@endsection

@section('content')
@include('ventas._detalle')
@endsection
