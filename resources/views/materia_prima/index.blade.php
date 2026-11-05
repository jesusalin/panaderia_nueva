@extends('layouts.app')
@section('title', 'Materia Prima')
@section('breadcrumb') <li class="breadcrumb-item active">Materia Prima</li> @endsection

@include('partials.inv-estilos')

@push('styles')
<style>
    .mp-card .item-card-media { height: 74px; }
    .mp-card .item-card-media > i { color: #b5451b; }
    .mp-card .item-card-title a { color: inherit; }
    .mp-card .item-card-title a:hover { color: #b5451b; text-decoration: none; }
    .mp-valor { font-size: .78rem; color: #8a8a9d; margin-top: .15rem; } .mp-valor b { color: #1a1a2e; } body.dark-mode .mp-valor b { color: #f0f0f7; }
    .mp-cob { display: flex; justify-content: space-between; align-items: center; font-size: .78rem; color: #8a8a9d; margin-top: .55rem; }
    .mp-cob b { color: #1a1a2e; }
    .mp-cob .corto b { color: #c0392b; }
    body.dark-mode .mp-cob b { color: #f0f0f7; }
    .mp-control { position: absolute; left: .55rem; top: .55rem; font-size: .66rem; font-weight: 800; padding: .15rem .5rem; border-radius: 20px; background: rgba(26,26,46,.75); color: #fff; }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-boxes mr-2 text-secondary"></i>Materia Prima</h2>
        <p>Insumos con los que se produce (harina, azúcar, lácteos, etc.)</p>
    </div>
    <div class="toolbar-actions">
        @if(auth()->user()->hasPermiso('inventario.conteo'))
        <a href="{{ route('conteo-fisico.index') }}" class="btn btn-outline-secondary"><i class="fas fa-clipboard-check mr-1"></i>Conteo físico</a>
        @endif
        <a href="{{ route('materia-prima.create') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo insumo
        </a>
    </div>
</div>

<div class="inv-resumen">
    <a href="{{ route('materia-prima.index') }}" class="ir {{ !request('stock') ? 'activo' : '' }}"><b>{{ $stats['total'] }}</b> insumos <small>({{ $stats['activos'] }} activos)</small></a>
    <a href="{{ route('materia-prima.index', ['stock' => 'bajo', 'estado' => 'activo', 'orden' => 'urgente']) }}" class="ir bajo {{ request('stock') === 'bajo' ? 'activo' : '' }}"><i class="fas fa-exclamation-triangle"></i><b>{{ $stats['bajo'] }}</b> con stock bajo</a>
    <a href="{{ route('materia-prima.index', ['stock' => 'agotado', 'estado' => 'activo']) }}" class="ir agotado {{ request('stock') === 'agotado' ? 'activo' : '' }}"><i class="fas fa-times-circle"></i><b>{{ $stats['agotados'] }}</b> agotados</a>
    <span class="ir valor" title="Stock × costo unitario de los insumos activos"><i class="fas fa-coins"></i>Valor del almacén: <b>S/ {{ number_format($stats['valor'], 2) }}</b></span>
</div>

<form method="GET" action="{{ route('materia-prima.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" data-sugerencias="insumos" autocomplete="off" class="form-control" placeholder="Buscar insumo..." value="{{ request('buscar') }}">
    </div>
    <label class="fb-label mb-0">Proveedor</label>
    <select name="proveedor" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="ninguno" {{ request('proveedor') === 'ninguno' ? 'selected' : '' }}>Sin proveedor</option>
        @foreach($proveedores as $pr)
            <option value="{{ $pr->id }}" {{ request('proveedor') == $pr->id ? 'selected' : '' }}>{{ $pr->nombre }}</option>
        @endforeach
    </select>
    <label class="fb-label mb-0">Stock</label>
    <select name="stock" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="ok"      {{ request('stock') === 'ok'      ? 'selected' : '' }}>Suficiente</option>
        <option value="bajo"    {{ request('stock') === 'bajo'    ? 'selected' : '' }}>Bajo</option>
        <option value="agotado" {{ request('stock') === 'agotado' ? 'selected' : '' }}>Agotado</option>
    </select>
    <label class="fb-label mb-0">Estado</label>
    <select name="estado" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="activo"   {{ request('estado') === 'activo'   ? 'selected' : '' }}>Activo</option>
        <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
    </select>
    <label class="fb-label mb-0">Ordenar</label>
    <select name="orden" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Nombre (A-Z)</option>
        <option value="urgente" {{ request('orden') === 'urgente' ? 'selected' : '' }}>Más urgentes de reponer</option>
        <option value="dias"    {{ request('orden') === 'dias'    ? 'selected' : '' }}>Menos días de stock</option>
        <option value="valor"   {{ request('orden') === 'valor'   ? 'selected' : '' }}>Mayor valor en almacén</option>
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('proveedor') || request('estado') || request('stock') || request('orden'))
        <a href="{{ route('materia-prima.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($materias->count() > 0)
<div class="card-grid">
    @foreach($materias as $m)
        @php
            $u = $m->unidad->abreviatura ?? '';
            // Barra llena al 100% cuando el stock duplica el mínimo
            $referencia = max($m->stock_minimo * 2, 0.001);
            $porcentaje = min(100, round(($m->stock_actual / $referencia) * 100));
            $porDia = $consumo[$m->id] ?? null;
            $dias = $m->diasCobertura($porDia);

            $usosMateria = [];
            if ($m->receta_detalles_count > 0)      $usosMateria[] = 'está en ' . $m->receta_detalles_count . ' receta(s)';
            if ($m->compra_detalles_count > 0)      $usosMateria[] = $m->compra_detalles_count . ' compra(s)';
            if ($m->movimientos_count > 0)          $usosMateria[] = 'movimientos de inventario';
            if ($m->ordenes_automaticas_count > 0)  $usosMateria[] = 'órdenes automáticas';
            $bloqueadoMateria = count($usosMateria) > 0;
        @endphp
        <div class="item-card mp-card {{ $m->estado !== 'activo' ? 'is-inactive' : '' }}">
            <a href="{{ route('materia-prima.show', $m) }}" class="item-card-media" style="text-decoration:none;" title="Ver ficha del insumo">
                <i class="fas fa-wheat-awn"></i>
                @if($m->es_control)<span class="mp-control" title="Se cuenta en el conteo rápido del PER">Control</span>@endif
                <span class="ic-badge">
                    @if($m->estado !== 'activo')
                        <span class="badge-soft badge-soft-secondary">Inactivo</span>
                    @elseif($m->estaAgotado())
                        <span class="badge-soft badge-soft-danger"><i class="fas fa-times-circle mr-1"></i>Agotado</span>
                    @elseif($m->tieneStockBajo())
                        <span class="badge-soft badge-soft-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Stock bajo</span>
                    @endif
                </span>
            </a>

            <div class="item-card-body">
                <div class="item-card-cat">{{ $m->proveedor->nombre ?? 'Sin proveedor asignado' }}</div>
                <h3 class="item-card-title"><a href="{{ route('materia-prima.show', $m) }}">{{ $m->nombre }}</a></h3>

                <div class="stock-gauge">
                    <div class="sg-track">
                        <div class="sg-fill {{ $m->tieneStockBajo() ? 'bajo' : 'ok' }}" style="width: {{ $porcentaje }}%;"></div>
                    </div>
                    <div class="sg-labels">
                        <span>Hay: <strong>{{ \App\Support\Cantidad::conUnidad($m->stock_actual, $u) }}</strong></span>
                        <span>Mín.: {{ \App\Support\Cantidad::conUnidad($m->stock_minimo, $u) }}</span>
                    </div>
                </div>

                <div class="item-card-price" style="font-size:1.05rem;">
                    S/ {{ number_format($m->costo_unitario, 2) }}
                    <span class="ic-cost">por {{ $u }}</span>
                </div>
                <div class="mp-valor">En almacén: <b>S/ {{ number_format($m->valor, 2) }}</b></div>
                <div class="mp-cob">
                    @if($dias !== null)
                        <span class="{{ $dias < 3 ? 'corto' : '' }}" title="Se usan ≈ {{ \App\Support\Cantidad::conUnidad(round($porDia, $porDia >= 1 ? 2 : 3), $u) }} al día">
                            <i class="far fa-calendar-alt mr-1"></i>Alcanza para <b>{{ $dias < 1 ? 'menos de 1 día' : $dias . ' día' . ($dias == 1 ? '' : 's') }}</b>
                        </span>
                    @else
                        <span><i class="far fa-calendar-alt mr-1"></i>Sin consumo reciente</span>
                    @endif
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
                    <a href="{{ route('materia-prima.ajuste', $m) }}" class="btn btn-icon btn-info" title="Ajustar stock (merma o conteo)">
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
    <a href="{{ route('materia-prima.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@endsection
