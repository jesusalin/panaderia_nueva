@extends('layouts.app')
@section('title', 'Clientes')
@section('breadcrumb') <li class="breadcrumb-item active">Clientes</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-users mr-2 text-success"></i>Clientes y Puntos de Distribución</h2>
        <p>Bodegas, supermercados, colegios y restaurantes a los que distribuyes</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('clientes.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Cliente
        </a>
    </div>
</div>

@if($clientes->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>Distrito</th>
                <th>Teléfono</th>
                <th class="text-center">Ventas</th>
                <th class="text-center">Estado</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clientes as $cliente)
            @php
                $tipo = $cliente->tipo ?? 'particular';
                $icono = ['bodega'=>'fa-store','colegio'=>'fa-school','restaurante'=>'fa-utensils','supermercado'=>'fa-cart-shopping'][$tipo] ?? 'fa-user';
            @endphp
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="row-icon"><i class="fas {{ $icono }}"></i></div>
                        <div class="ml-2">
                            <div class="row-title">{{ $cliente->nombre }}</div>
                            @if($cliente->email)<div class="row-subtitle">{{ $cliente->email }}</div>@endif
                        </div>
                    </div>
                </td>
                <td><span class="badge-soft badge-soft-info">{{ ucfirst($tipo) }}</span></td>
                <td>{{ $cliente->distrito ?? '—' }}</td>
                <td>{{ $cliente->telefono ?? '—' }}</td>
                <td class="text-center"><span class="badge-soft badge-soft-info">{{ $cliente->ventas_count }}</span></td>
                <td class="text-center">
                    <span class="badge-soft {{ ($cliente->estado ?? 'activo') === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ ucfirst($cliente->estado ?? 'activo') }}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-icon btn-info" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-icon btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($cliente->nombre !== 'Cliente General')
                        <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Desactivar cliente?"
                            data-confirm="&quot;{{ $cliente->nombre }}&quot; ya no aparecerá disponible para nuevas ventas.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Desactivar"><i class="fas fa-ban"></i></button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="card-footer bg-white">{{ $clientes->links() }}</div>
</div>
@else
<div class="empty-state">
    <i class="fas fa-users"></i>
    <p>Todavía no tienes clientes registrados</p>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@endsection
