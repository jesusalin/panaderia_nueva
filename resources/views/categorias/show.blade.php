@extends('layouts.app')
@section('title', $categoria->nombre)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('categorias.index') }}">Categorías</a></li>
    <li class="breadcrumb-item active">{{ $categoria->nombre }}</li>
@endsection

@push('styles')
<style>
    .cs-head {
        border-radius: 14px; color: #fff; padding: 1.5rem 1.75rem; display: flex; align-items: center; gap: 1.1rem; flex-wrap: wrap;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 55%, var(--cat-color) 100%); margin-bottom: 1.25rem;
    }
    .cs-icono { width: 60px; height: 60px; border-radius: 14px; background: rgba(255,255,255,.15); border: 2px solid rgba(255,255,255,.3);
        display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0; }
    .cs-head h2 { margin: 0; font-weight: 800; font-size: 1.45rem; }
    .cs-head p { margin: .1rem 0 0; opacity: .8; font-size: .88rem; }
    .cs-head .cs-acciones { margin-left: auto; display: flex; gap: .5rem; }
    .cs-datos { display: flex; gap: 1.6rem; flex-wrap: wrap; margin-top: .4rem; font-size: .82rem; opacity: .9; }
    .cs-datos b { font-size: 1.05rem; }

    .cs-tabla { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); overflow: hidden; }
    .cs-tabla table { margin: 0; }
    .cs-tabla th { font-size: .74rem; text-transform: uppercase; letter-spacing: .04em; color: #8a8a9d; border-top: none; background: #faf9f7; }
    .cs-tabla td { vertical-align: middle; font-size: .9rem; }
    .cs-tabla tr.inactivo td { opacity: .55; }
    .cs-stock { display: inline-flex; align-items: center; gap: .4rem; font-weight: 700; }
    .cs-stock .dot { width: 9px; height: 9px; border-radius: 50%; }
    .cs-stock.bajo .dot { background: #e74c3c; } .cs-stock.bajo { color: #c0392b; }
    .cs-stock.medio .dot { background: #f39c12; }
    .cs-stock.ok .dot { background: #2ecc71; }

    .cs-eliminar { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1.3rem 1.5rem; margin-top: 1.25rem; border-left: 4px solid #e74c3c; }
    .cs-eliminar h5 { font-weight: 800; color: #1a1a2e; font-size: 1rem; }
    .cs-eliminar p { color: #6c757d; font-size: .87rem; }
    body.dark-mode .cs-tabla, body.dark-mode .cs-eliminar { background: #1f1f33; }
    body.dark-mode .cs-tabla th { background: #29293f; }
    body.dark-mode .cs-eliminar h5 { color: #f0f0f7; }
</style>
@endpush

@section('content')
@php
    $activos = $productos->where('estado', 'activo');
    $bajos   = $activos->filter(fn ($p) => $p->tieneStockBajo());
    $puedeEditarProductos = auth()->user()->hasPermiso('catalogo.productos');
@endphp

<div class="cs-head" style="--cat-color: {{ $categoria->color_final }}">
    <div class="cs-icono"><i class="fas {{ $categoria->icono_final }}"></i></div>
    <div>
        <h2>{{ $categoria->nombre }}
            @if($categoria->estado !== 'activo')<span class="badge badge-light ml-1" style="font-size:.7rem;vertical-align:middle;">Inactiva</span>@endif
        </h2>
        <p>{{ $categoria->descripcion ?? 'Sin descripción' }}</p>
        <div class="cs-datos">
            <span><b>{{ $productos->count() }}</b> producto(s)</span>
            <span><b>{{ $activos->count() }}</b> activo(s)</span>
            <span><b>{{ $bajos->count() }}</b> con stock bajo</span>
            @if($activos->count())
                <span>Precio: <b>S/ {{ number_format($activos->min('precio_venta'), 2) }}</b>@if($activos->min('precio_venta') != $activos->max('precio_venta')) – <b>S/ {{ number_format($activos->max('precio_venta'), 2) }}</b>@endif</span>
            @endif
        </div>
    </div>
    <div class="cs-acciones">
        <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-light btn-sm"><i class="fas fa-edit mr-1"></i>Editar</a>
        <a href="{{ route('categorias.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
    </div>
</div>

<div class="cs-tabla" id="productos">
    @if($productos->count())
    <div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Costo</th>
                <th>Stock</th>
                <th>Estado</th>
                @if($puedeEditarProductos)<th></th>@endif
            </tr>
        </thead>
        <tbody>
        @foreach($productos as $p)
            @php
                $nivel = $p->tieneStockBajo() ? 'bajo' : ($p->stock_actual <= $p->stock_minimo * 1.5 ? 'medio' : 'ok');
            @endphp
            <tr class="{{ $p->estado !== 'activo' ? 'inactivo' : '' }}">
                <td><strong>{{ $p->nombre }}</strong>@if($p->descripcion)<br><small class="text-muted">{{ \Illuminate\Support\Str::limit($p->descripcion, 60) }}</small>@endif</td>
                <td class="text-right">S/ {{ number_format($p->precio_venta, 2) }}</td>
                <td class="text-right text-muted">S/ {{ number_format($p->costo_produccion, 2) }}</td>
                <td>
                    <span class="cs-stock {{ $nivel }}" title="Mínimo: {{ $p->stock_minimo }}">
                        <span class="dot"></span>{{ $p->stock_actual }} <small class="text-muted font-weight-normal">/ mín. {{ $p->stock_minimo }}</small>
                    </span>
                </td>
                <td><span class="badge-soft {{ $p->estado === 'activo' ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ ucfirst($p->estado) }}</span></td>
                @if($puedeEditarProductos)
                    <td class="text-right"><a href="{{ route('productos.edit', $p) }}" class="btn btn-sm btn-light" title="Editar producto"><i class="fas fa-edit"></i></a></td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @else
        <div class="empty-state" style="padding:2.5rem 1rem;">
            <i class="fas fa-box-open"></i>
            <p class="mb-0">Esta categoría todavía no tiene productos</p>
        </div>
    @endif
</div>

@if($productos->count() === 0)
<div class="cs-eliminar" id="eliminar">
    <h5><i class="fas fa-trash mr-1 text-danger"></i>Eliminar categoría</h5>
    <p class="mb-3">No tiene productos, así que se puede eliminar sin problema.</p>
    <form action="{{ route('categorias.destroy', $categoria) }}" method="POST" class="js-confirm"
          data-confirm-title="¿Eliminar esta categoría?"
          data-confirm="&quot;{{ $categoria->nombre }}&quot; se borrará por completo. Esta acción no se puede deshacer.">
        @csrf @method('DELETE')
        <button class="btn btn-danger btn-sm"><i class="fas fa-trash mr-1"></i>Eliminar</button>
    </form>
</div>
@endif
@endsection
