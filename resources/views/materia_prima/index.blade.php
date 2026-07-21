@extends('layouts.app')
@section('title', 'Materia Prima')
@section('breadcrumb') <li class="breadcrumb-item active">Materia Prima</li> @endsection

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-boxes mr-2 text-secondary"></i>Materia Prima</h2>
        <p>Insumos con los que se produce (harina, azúcar, lácteos, etc.)</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('materia-prima.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nueva Materia Prima
        </a>
    </div>
</div>

<form method="GET" action="{{ route('materia-prima.index') }}" class="filter-bar">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar insumo..." value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Proveedor</label>
    <select name="proveedor" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        @foreach($proveedores as $pr)
            <option value="{{ $pr->id }}" {{ request('proveedor') == $pr->id ? 'selected' : '' }}>{{ $pr->nombre }}</option>
        @endforeach
    </select>
    <label class="fb-label mb-0">Stock</label>
    <select name="stock" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="bajo" {{ request('stock') === 'bajo' ? 'selected' : '' }}>Solo stock bajo</option>
    </select>
    <label class="fb-label mb-0">Estado</label>
    <select name="estado" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="activo"   {{ request('estado') === 'activo'   ? 'selected' : '' }}>Activo</option>
        <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('proveedor') || request('estado') || request('stock'))
        <a href="{{ route('materia-prima.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($materias->count() > 0)
<div class="card-grid">
    @foreach($materias as $m)
        @php
            // Referencia visual: barra llena al 100% cuando el stock duplica el mínimo
            $referencia = max($m->stock_minimo * 2, 0.001);
            $porcentaje = min(100, round(($m->stock_actual / $referencia) * 100));

            $usosMateria = [];
            if ($m->receta_detalles_count > 0)      $usosMateria[] = 'está en ' . $m->receta_detalles_count . ' receta(s)';
            if ($m->compra_detalles_count > 0)      $usosMateria[] = $m->compra_detalles_count . ' compra(s)';
            if ($m->movimientos_count > 0)           $usosMateria[] = 'movimientos de inventario';
            if ($m->ordenes_automaticas_count > 0)   $usosMateria[] = 'órdenes automáticas';
            $bloqueadoMateria = count($usosMateria) > 0;
        @endphp
        <div class="item-card {{ $m->estado !== 'activo' ? 'is-inactive' : '' }}">
            <div class="item-card-media" style="height:80px;">
                <i class="fas fa-wheat-awn"></i>
                <span class="ic-badge">
                    @if($m->tieneStockBajo())
                        <span class="badge-soft badge-soft-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Stock bajo</span>
                    @elseif($m->estado !== 'activo')
                        <span class="badge-soft badge-soft-secondary">Inactivo</span>
                    @endif
                </span>
            </div>

            <div class="item-card-body">
                <div class="item-card-cat">{{ $m->proveedor->nombre ?? 'Sin proveedor asignado' }}</div>
                <h3 class="item-card-title">{{ $m->nombre }}</h3>

                <div class="stock-gauge">
                    <div class="sg-track">
                        <div class="sg-fill {{ $m->tieneStockBajo() ? 'bajo' : 'ok' }}" style="width: {{ $porcentaje }}%;"></div>
                    </div>
                    <div class="sg-labels">
                        <span>Actual: <strong>{{ number_format($m->stock_actual, 2) }} {{ $m->unidad->abreviatura }}</strong></span>
                        <span>Mínimo: {{ number_format($m->stock_minimo, 2) }} {{ $m->unidad->abreviatura }}</span>
                    </div>
                </div>

                <div class="item-card-price" style="font-size:1.05rem;">
                    S/ {{ number_format($m->costo_unitario, 2) }}
                    <span class="ic-cost">por {{ $m->unidad->nombre }}</span>
                </div>
            </div>

            <div class="item-card-footer">
                <form action="{{ route('materia-prima.toggle-estado', $m) }}" method="POST">
                    @csrf @method('PUT')
                    <button type="submit" class="estado-switch {{ $m->estado === 'activo' ? 'activa' : '' }}">
                        <span class="track"></span>
                        <span class="txt">{{ $m->estado === 'activo' ? 'Activo' : 'Inactivo' }}</span>
                    </button>
                </form>
                <div class="btn-icon-group">
                    <a href="{{ route('materia-prima.ajuste', $m) }}" class="btn btn-icon btn-info" title="Ajustar inventario">
                        <i class="fas fa-balance-scale"></i>
                    </a>
                    <a href="{{ route('materia-prima.edit', $m) }}" class="btn btn-icon btn-warning" title="Editar">
                        <i class="fas fa-edit"></i>
                    </a>
                    @if($bloqueadoMateria)
                        <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked"
                            data-blocked-title="No se puede eliminar este insumo"
                            data-blocked-message="&quot;{{ $m->nombre }}&quot; {{ implode(' y ', $usosMateria) }}. Usa el interruptor para desactivarlo sin perder su historial.">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    @else
                        <form action="{{ route('materia-prima.destroy', $m) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Eliminar este insumo?"
                            data-confirm="&quot;{{ $m->nombre }}&quot; se borrará por completo del sistema. Esta acción NO se puede deshacer.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-3">{{ $materias->links() }}</div>
@else
<div class="empty-state">
    <i class="fas fa-boxes"></i>
    <p>{{ request('buscar') || request('proveedor') || request('estado') || request('stock') ? 'Ningún insumo coincide con ese filtro' : 'Todavía no tienes materia prima registrada' }}</p>
    <a href="{{ route('materia-prima.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear la primera</a>
</div>
@endif

@endsection
