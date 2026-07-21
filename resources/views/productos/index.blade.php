@extends('layouts.app')
@section('title', 'Productos')
@section('breadcrumb') <li class="breadcrumb-item active">Productos</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-bread-slice mr-2 text-warning"></i>Productos</h2>
        <p>Catálogo de productos terminados que la panadería fabrica y vende</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('productos.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Producto
        </a>
    </div>
</div>

<form method="GET" action="{{ route('productos.index') }}" class="filter-bar">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar producto..." value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Categoría</label>
    <select name="categoria" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todas</option>
        @foreach($categorias as $c)
            <option value="{{ $c->id }}" {{ request('categoria') == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
        @endforeach
    </select>
    <label class="fb-label mb-0">Estado</label>
    <select name="estado" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="activo"   {{ request('estado') === 'activo'   ? 'selected' : '' }}>Activo</option>
        <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('categoria') || request('estado'))
        <a href="{{ route('productos.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($productos->count() > 0)
<div class="card-grid">
    @foreach($productos as $p)
        @php
            $nombreLower = mb_strtolower($p->categoria->nombre ?? '');
            $icono = 'fa-bread-slice';
            if (str_contains($nombreLower, 'pastel') || str_contains($nombreLower, 'torta')) $icono = 'fa-birthday-cake';
            elseif (str_contains($nombreLower, 'galleta'))                  $icono = 'fa-cookie';
            elseif (str_contains($nombreLower, 'empanada'))                 $icono = 'fa-cheese';
            elseif (str_contains($nombreLower, 'bebida') || str_contains($nombreLower, 'café')) $icono = 'fa-mug-hot';
        @endphp
        @php
            $usosProducto = [];
            if ($p->venta_detalles_count > 0) $usosProducto[] = $p->venta_detalles_count . ' venta(s)';
            if ($p->producciones_count > 0)   $usosProducto[] = $p->producciones_count . ' producción(es)';
            if ($p->receta_count > 0)         $usosProducto[] = 'una receta';
            if ($p->kardex_count > 0)         $usosProducto[] = 'movimientos de kardex';
            $bloqueadoProducto = count($usosProducto) > 0;
        @endphp
        <div class="item-card {{ $p->estado !== 'activo' ? 'is-inactive' : '' }}">
            <div class="item-card-media">
                @if($p->imagen)
                    <img src="{{ asset('storage/'.$p->imagen) }}" alt="{{ $p->nombre }}">
                @else
                    <i class="fas {{ $icono }}"></i>
                @endif

                <span class="ic-badge">
                    @if($p->tieneStockBajo())
                        <span class="badge-soft badge-soft-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Stock bajo</span>
                    @elseif($p->estado !== 'activo')
                        <span class="badge-soft badge-soft-secondary">Inactivo</span>
                    @endif
                </span>
            </div>

            <div class="item-card-body">
                <div class="item-card-cat">{{ $p->categoria->nombre ?? 'Sin categoría' }}</div>
                <h3 class="item-card-title">{{ $p->nombre }}</h3>

                <div class="item-card-price">
                    S/ {{ number_format($p->precio_venta, 2) }}
                    @if($p->costo_produccion)
                        <span class="ic-cost">costo S/ {{ number_format($p->costo_produccion, 2) }}</span>
                    @endif
                </div>

                <div class="item-card-stockrow">
                    <span>Stock disponible</span>
                    <span class="badge-soft {{ $p->tieneStockBajo() ? 'badge-soft-danger' : 'badge-soft-success' }}">
                        {{ $p->stock_actual }} uds
                    </span>
                </div>
            </div>

            <div class="item-card-footer">
                <form action="{{ route('productos.toggle-estado', $p) }}" method="POST">
                    @csrf @method('PUT')
                    <button type="submit" class="estado-switch {{ $p->estado === 'activo' ? 'activa' : '' }}">
                        <span class="track"></span>
                        <span class="txt">{{ $p->estado === 'activo' ? 'Activo' : 'Inactivo' }}</span>
                    </button>
                </form>
                <div class="btn-icon-group">
                    <a href="{{ route('productos.edit', $p) }}" class="btn btn-icon btn-warning" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    @if($bloqueadoProducto)
                        <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                            data-blocked-title="No se puede eliminar este producto"
                            data-blocked-message="&quot;{{ $p->nombre }}&quot; tiene {{ implode(' y ', $usosProducto) }}. Usa el interruptor para desactivarlo sin perder su historial.">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @else
                        <form action="{{ route('productos.destroy', $p) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Eliminar este producto?"
                            data-confirm="&quot;{{ $p->nombre }}&quot; se borrará por completo del sistema. Esta acción NO se puede deshacer.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-3">{{ $productos->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-bread-slice"></i>
    <p>{{ request('buscar') || request('categoria') || request('estado') ? 'Ningún producto coincide con ese filtro' : 'Todavía no tienes productos registrados' }}</p>
    <a href="{{ route('productos.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@endsection
