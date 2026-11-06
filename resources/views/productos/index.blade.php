@extends('layouts.app')
@section('title', 'Productos')
@section('breadcrumb') <li class="breadcrumb-item active">Productos</li> @endsection

@section('content')

@include('partials.tabs-productos')

@php $puedeVender = auth()->user()->hasModulo('ventas'); @endphp

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

@if($puedeVender && $ultimaVenta)
    <div class="vr-ultima">
        <span class="ic"><i class="fas fa-check"></i></span>
        <div class="flex-grow-1">
            <b>Venta {{ $ultimaVenta->numero_venta }} registrada · S/ {{ number_format($ultimaVenta->total, 2) }}</b>
            @if($ultimaVenta->vuelto !== null)<div style="font-size:.9rem">Vuelto: <b>S/ {{ number_format($ultimaVenta->vuelto, 2) }}</b></div>@endif
        </div>
        <a href="{{ route('ventas.ticket', $ultimaVenta) }}" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-receipt mr-1"></i>Imprimir ticket</a>
        <a href="{{ route('ventas.show', $ultimaVenta) }}" class="btn btn-light btn-sm">Ver venta</a>
    </div>
@endif

<div class="prod-stats">
    <a href="{{ route('productos.index') }}" class="ps-item {{ !request('stock') ? 'activo' : '' }}"><b>{{ $stats['total'] }}</b> productos <small>({{ $stats['activos'] }} activos)</small></a>
    <a href="{{ route('productos.index', ['stock' => 'bajo', 'estado' => 'activo']) }}" class="ps-item ps-bajo {{ request('stock') === 'bajo' ? 'activo' : '' }}"><i class="fas fa-exclamation-triangle"></i><b>{{ $stats['bajo'] }}</b> con stock bajo</a>
    <a href="{{ route('productos.index', ['stock' => 'agotado', 'estado' => 'activo']) }}" class="ps-item ps-agotado {{ request('stock') === 'agotado' ? 'activo' : '' }}"><i class="fas fa-times-circle"></i><b>{{ $stats['agotados'] }}</b> agotados</a>
</div>

<form method="GET" action="{{ route('productos.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <div class="search-box flex-grow-1" style="min-width:220px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" data-sugerencias="productos" autocomplete="off" class="form-control" placeholder="Buscar por nombre o descripción..." value="{{ request('buscar') }}">
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
    <label class="fb-label mb-0">Stock</label>
    <select name="stock" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Todos</option>
        <option value="ok"      {{ request('stock') === 'ok'      ? 'selected' : '' }}>Suficiente</option>
        <option value="bajo"    {{ request('stock') === 'bajo'    ? 'selected' : '' }}>Bajo</option>
        <option value="agotado" {{ request('stock') === 'agotado' ? 'selected' : '' }}>Agotado</option>
    </select>
    <label class="fb-label mb-0">Ordenar</label>
    <select name="orden" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <option value="">Nombre (A-Z)</option>
        <option value="vendidos"    {{ request('orden') === 'vendidos'    ? 'selected' : '' }}>Más vendidos (30 días)</option>
        <option value="stock"       {{ request('orden') === 'stock'       ? 'selected' : '' }}>Más urgentes de reponer</option>
        <option value="margen"      {{ request('orden') === 'margen'      ? 'selected' : '' }}>Mayor ganancia (%)</option>
        <option value="precio_asc"  {{ request('orden') === 'precio_asc'  ? 'selected' : '' }}>Precio: menor a mayor</option>
        <option value="precio_desc" {{ request('orden') === 'precio_desc' ? 'selected' : '' }}>Precio: mayor a menor</option>
    </select>
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request('buscar') || request('categoria') || request('estado') || request('stock') || request('orden'))
        <a href="{{ route('productos.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($productos->count() > 0)
<div class="card-grid">
    @foreach($productos as $p)
        @php
            $icono = $p->categoria->icono_final ?? 'fa-bread-slice';
            $color = $p->categoria->color_final ?? '#b5451b';
            $vendidos = (int) $p->vendidos_30;
            // Barra de stock: llena = 3 veces el mínimo
            $tope = max(1, $p->stock_minimo * 3);
            $pct = min(100, round($p->stock_actual / $tope * 100));
            $nivel = $p->estaAgotado() ? 'agotado' : ($p->tieneStockBajo() ? 'bajo' : 'ok');
        @endphp
        @php
            $usosProducto = [];
            if ($p->venta_detalles_count > 0) $usosProducto[] = $p->venta_detalles_count . ' venta(s)';
            if ($p->producciones_count > 0)   $usosProducto[] = $p->producciones_count . ' producción(es)';
            if ($p->receta_count > 0)         $usosProducto[] = 'una receta';
            if ($p->kardex_count > 0)         $usosProducto[] = 'movimientos de kardex';
            $bloqueadoProducto = count($usosProducto) > 0;
        @endphp
        <div class="item-card clic-tarjeta {{ $p->estado !== 'activo' ? 'is-inactive' : '' }}" style="--cat-color: {{ $color }}" data-href="{{ route('productos.show', $p) }}" role="link" tabindex="0" aria-label="Ver ficha de {{ $p->nombre }}">
            <span class="clic-ver">Ver ficha <i class="fas fa-chevron-right ml-1"></i></span>
            <a href="{{ route('productos.show', $p) }}" class="item-card-media prod-media" title="Ver ficha del producto">
                @if($p->imagen)
                    <img src="{{ asset('storage/'.$p->imagen) }}" alt="{{ $p->nombre }}">
                @else
                    <i class="fas {{ $icono }}"></i>
                @endif

                <span class="ic-badge">
                    @if($p->estado !== 'activo')
                        <span class="badge-soft badge-soft-secondary">Inactivo</span>
                    @elseif($nivel === 'agotado')
                        <span class="badge-soft badge-soft-danger"><i class="fas fa-times-circle mr-1"></i>Agotado</span>
                    @elseif($nivel === 'bajo')
                        <span class="badge-soft badge-soft-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Stock bajo</span>
                    @endif
                </span>
                @if($vendidos > 0)
                    <span class="prod-vendidos" title="Unidades vendidas en los últimos 30 días"><i class="fas fa-fire mr-1"></i>{{ $vendidos }} vendidos</span>
                @endif
            </a>

            <div class="item-card-body">
                <div class="item-card-cat prod-cat"><i class="fas {{ $icono }} mr-1"></i>{{ $p->categoria->nombre ?? 'Sin categoría' }}</div>
                <h3 class="item-card-title"><a href="{{ route('productos.show', $p) }}">{{ $p->nombre }}</a></h3>

                <div class="item-card-price">
                    S/ {{ number_format($p->precio_venta, 2) }}
                    @if($p->margen !== null)
                        <span class="prod-margen {{ $p->margen < 0 ? 'perdida' : ($p->margen < 20 ? 'baja' : '') }}"
                              title="Costo S/ {{ number_format($p->costo_produccion, 2) }} · ganas S/ {{ number_format($p->ganancia, 2) }} por unidad">
                            {{ $p->margen < 0 ? 'Pierdes' : 'Ganas' }} {{ abs($p->margen) }}%
                        </span>
                    @else
                        <span class="ic-cost">sin costo registrado</span>
                    @endif
                </div>

                <div class="item-card-stockrow">
                    <span>Stock <small class="text-muted">(mín. {{ $p->stock_minimo }})</small></span>
                    <span class="badge-soft {{ $nivel === 'ok' ? 'badge-soft-success' : 'badge-soft-danger' }}">
                        {{ $p->stock_actual }} uds
                    </span>
                </div>
                <div class="prod-barra {{ $nivel }}"><span style="width: {{ $pct }}%"></span></div>

                @if($puedeVender)
                    @if($p->estado === 'activo' && $p->stock_actual > 0)
                        <button type="button" class="btn btn-cart-add" data-id="{{ $p->id }}"
                            onclick="carritoAgregar(this)">
                            <i class="fas fa-cart-plus mr-1"></i>Agregar a la venta
                        </button>
                    @else
                        <button type="button" class="btn btn-cart-add" disabled>
                            <i class="fas fa-ban mr-1"></i>No disponible
                        </button>
                    @endif
                @endif
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
                    <a href="{{ route('productos.ajuste', ['producto' => $p, 'volver' => request()->fullUrl()]) }}" class="btn btn-icon btn-info" title="Ajustar stock (merma o conteo)">
                        <i class="fas fa-balance-scale"></i>
                    </a>
                    <a href="{{ route('productos.edit', ['producto' => $p, 'volver' => request()->fullUrl()]) }}" class="btn btn-icon btn-warning" title="Editar">
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
    <p>{{ request('buscar') || request('categoria') || request('estado') || request('stock') ? 'Ningún producto coincide con ese filtro' : 'Todavía no tienes productos registrados' }}</p>
    <a href="{{ route('productos.create') }}" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Crear el primero</a>
</div>
@endif

@if($puedeVender)
{{-- ══════════ VENTA RÁPIDA (carrito flotante) ══════════ --}}
<button type="button" class="cart-fab" id="cartFab" onclick="carritoToggle()" aria-label="Abrir la venta rápida">
    <i class="fas fa-shopping-basket"></i>
    <span class="cart-fab-total" id="cartFabTotal"></span>
    <span class="cart-fab-badge" id="cartBadge" style="display:none;">0</span>
</button>

<div class="cart-overlay" id="cartOverlay" onclick="carritoToggle(false)"></div>

<div class="cart-drawer" id="cartDrawer" role="dialog" aria-modal="true" aria-labelledby="cartTitulo">
    <div class="cart-drawer-head">
        <h5 id="cartTitulo"><i class="fas fa-shopping-basket mr-2"></i>Venta rápida <span id="cartN" class="cart-n"></span></h5>
        <div class="d-flex align-items-center" style="gap:.4rem">
            <button type="button" class="cart-vaciar" id="cartVaciar" title="Vaciar la venta"><i class="fas fa-trash-alt mr-1"></i>Vaciar</button>
            <button type="button" class="cart-close" onclick="carritoToggle(false)" aria-label="Cerrar"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <form action="{{ route('ventas.store') }}" method="POST" id="formCarrito" class="cart-form" autocomplete="off">
        @csrf
        <input type="hidden" name="volver" value="productos">

        <div class="cart-drawer-body">
            {{-- Buscar y agregar cualquier producto, aunque no esté en esta página --}}
            <div class="cart-buscar">
                <i class="fas fa-search"></i>
                <input type="search" id="cartBuscar" class="form-control" placeholder="Buscar producto o categoría…" aria-label="Buscar producto para agregar"
                       role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="cartSug" enterkeyhint="done">
                <div class="cart-sug" id="cartSug" role="listbox"></div>
            </div>
            <div class="cart-ayuda"><b>3 pan</b> agrega 3 de una vez · ↑ ↓ y Enter para elegir</div>
            <div class="cart-rapidos" id="cartRapidos"></div>
            <div id="cartItems"></div>
            <div id="cartHiddenInputs"></div>
        </div>

        <div class="cart-drawer-foot">
            @if($errors->any())
                <div class="cart-error"><i class="fas fa-exclamation-circle mr-1"></i>{{ $errors->first() }}</div>
            @endif
            <div class="cart-fila2">
                <div>
                    <label class="cart-label" for="cartCliente">Cliente</label>
                    <select name="id_cliente" id="cartCliente" class="form-control form-control-sm">
                        @forelse($clientes as $c)
                            <option value="{{ $c->id }}" {{ (int) old('id_cliente', $idGeneral) === $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                        @empty
                            <option value="">Cliente General</option>
                        @endforelse
                    </select>
                </div>
            </div>

            <span class="cart-label">Pago</span>
            <div class="cart-medios">
                @foreach(\App\Models\Venta::MEDIOS as $clave => $m)
                    <input type="radio" name="tipo_pago" id="cpago-{{ $clave }}" value="{{ $clave }}" {{ old('tipo_pago', 'efectivo') === $clave ? 'checked' : '' }}>
                    <label for="cpago-{{ $clave }}" style="--c: {{ $m['color'] }}"><i class="fas {{ $m['icono'] }}"></i>{{ $m['texto'] }}</label>
                @endforeach
            </div>

            <div id="cartEfectivo">
                <div class="cart-recibido">
                    <label class="cart-label mb-0" for="cartRecibido">Recibido</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                        <input type="number" step="0.10" min="0" name="monto_recibido" id="cartRecibido" class="form-control" value="{{ old('monto_recibido') }}" placeholder="para el vuelto" inputmode="decimal">
                    </div>
                </div>
                <div class="cart-billetes" id="cartBilletes"></div>
                <div class="cart-vuelto" id="cartVuelto" hidden><span id="cartVueltoTxt">Vuelto</span><b id="cartVueltoMonto"></b></div>
            </div>
            <div id="cartOperacion" hidden>
                <input type="text" name="numero_operacion" maxlength="30" class="form-control form-control-sm" value="{{ old('numero_operacion') }}" placeholder="N° de operación (opcional)">
            </div>
            <details class="cart-nota" {{ old('observaciones') ? 'open' : '' }}>
                <summary>Agregar una nota</summary>
                <input type="text" name="observaciones" maxlength="300" class="form-control form-control-sm mt-1" value="{{ old('observaciones') }}" placeholder="Ej. pedido para recoger a las 5 pm">
            </details>

            <div class="cart-totals">
                <div class="ct-row ct-muted"><span>Op. gravada</span><span id="ctSubtotal">S/ 0.00</span></div>
                <div class="ct-row ct-muted"><span>IGV (18%) incluido</span><span id="ctIgv">S/ 0.00</span></div>
                <div class="ct-row ct-total"><span>Total</span><span id="ctTotal">S/ 0.00</span></div>
            </div>

            <button type="submit" class="btn btn-success btn-block btn-cobrar" id="btnCartSubmit" disabled>
                <i class="fas fa-check-circle mr-1"></i>Cobrar
            </button>
        </div>
    </form>
</div>
@endif

@endsection

@push('styles')
<style>
    .prod-stats { display: flex; flex-wrap: wrap; gap: .6rem; margin-bottom: 1rem; }
    .prod-stats .ps-item { background: #fff; border-radius: 10px; padding: .5rem .9rem; font-size: .85rem; color: #6c757d;
        box-shadow: 0 1px 6px rgba(0,0,0,.05); border: 1.5px solid transparent; text-decoration: none; display: inline-flex; align-items: center; gap: .35rem; }
    .prod-stats .ps-item b { color: #1a1a2e; font-size: 1rem; }
    .prod-stats .ps-item.activo { border-color: #b5451b; }
    .prod-stats .ps-bajo i { color: #e67e22; } .prod-stats .ps-agotado i { color: #c0392b; }
    body.dark-mode .prod-stats .ps-item { background: #1f1f33; color: #b0b0cc; }
    body.dark-mode .prod-stats .ps-item b { color: #f0f0f7; }

    .item-card .prod-media { display: flex; text-decoration: none; position: relative; }
    .item-card .prod-media > i { color: var(--cat-color, #b5451b); }
    .prod-vendidos { position: absolute; left: .55rem; bottom: .55rem; background: rgba(26,26,46,.78); color: #fff;
        font-size: .7rem; font-weight: 700; padding: .2rem .55rem; border-radius: 20px; }
    .prod-cat { color: var(--cat-color, #b5451b) !important; }
    .item-card-title a { color: inherit; }
    .item-card-title a:hover { color: #b5451b; text-decoration: none; }
    .prod-margen { font-size: .72rem; font-weight: 800; padding: .15rem .5rem; border-radius: 20px; margin-left: .3rem;
        background: rgba(46,204,113,.14); color: #1e8e5a; vertical-align: middle; }
    .prod-margen.baja { background: rgba(243,156,18,.16); color: #a5680d; }
    .prod-margen.perdida { background: rgba(231,76,60,.14); color: #c0392b; }
    .prod-barra { height: 6px; border-radius: 6px; background: #eef0f3; overflow: hidden; margin-top: .35rem; }
    .prod-barra span { display: block; height: 100%; border-radius: 6px; background: #2ecc71; }
    .prod-barra.bajo span { background: #f39c12; } .prod-barra.agotado span { background: #e74c3c; }
    body.dark-mode .prod-barra { background: #2c2c44; }
</style>
@endpush

@if($puedeVender)
@push('styles')
<style>
    .btn-cart-add {
        width: 100%; margin-top: .7rem; border: none; border-radius: 9px; padding: .5rem;
        background: rgba(181,69,27,.1); color: #b5451b; font-weight: 700; font-size: .82rem; transition: all .15s;
    }
    .btn-cart-add:hover:not(:disabled) { background: #b5451b; color: #fff; }
    .btn-cart-add:disabled { background: #f1f1f4; color: #adb5bd; cursor: not-allowed; }
    .btn-cart-add.en-venta { background: #e5f6ec; color: #1e8e5a; }
    .btn-cart-add.en-venta:hover:not(:disabled) { background: #1e8e5a; color: #fff; }
    body.dark-mode .btn-cart-add { background: rgba(181,69,27,.22); color: #ff9d6e; }
    body.dark-mode .btn-cart-add.en-venta { background: rgba(30,142,90,.2); color: #6ee7a5; }
    body.dark-mode .btn-cart-add:hover:not(:disabled) { background: #b5451b; color: #fff; }
    body.dark-mode .btn-cart-add:disabled { background: #2c2c44; color: #5c5c78; }

    .vr-ultima { display: flex; align-items: center; gap: .8rem; flex-wrap: wrap; background: #e5f6ec; border-radius: 14px; padding: .7rem 1rem; margin-bottom: 1rem; color: #155d3b; }
    .vr-ultima .ic { width: 38px; height: 38px; border-radius: 50%; background: #1e8e5a; color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    body.dark-mode .vr-ultima { background: rgba(30,142,90,.16); color: #a7f0c8; }

    .cart-fab {
        position: fixed; right: 1.5rem; bottom: 1.5rem; height: 58px; min-width: 58px; border-radius: 29px; padding: 0 1.1rem;
        background: linear-gradient(135deg, #1a1a2e, #b5451b); color: #fff; border: none; font-size: 1.25rem;
        box-shadow: 0 8px 22px rgba(181,69,27,.4); z-index: 1040; cursor: pointer; transition: transform .15s;
        display: flex; align-items: center; justify-content: center; gap: .55rem;
    }
    .cart-fab:hover { transform: scale(1.05); }
    .cart-fab-total { font-size: .95rem; font-weight: 800; }
    .cart-fab-total:empty { display: none; }
    .cart-fab-badge {
        position: absolute; top: -4px; right: -4px; background: #e74c3c; color: #fff; font-size: .68rem; font-weight: 800;
        min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 4px; border: 2px solid #fff;
    }
    .cart-fab.bump { animation: cartBump .3s ease; }
    @keyframes cartBump { 0%,100% { transform: scale(1); } 40% { transform: scale(1.12); } }

    .cart-overlay { position: fixed; inset: 0; background: rgba(10,10,20,.45); z-index: 1049; opacity: 0; pointer-events: none; transition: opacity .2s; }
    .cart-overlay.show { opacity: 1; pointer-events: auto; }

    .cart-drawer {
        position: fixed; top: 0; right: 0; width: 400px; max-width: 100vw; height: 100vh; height: 100dvh; background: #fff;
        z-index: 1050; box-shadow: -8px 0 30px rgba(0,0,0,.15); display: flex; flex-direction: column;
        transform: translateX(105%); transition: transform .25s ease; visibility: hidden;
    }
    .cart-drawer.show { transform: none; visibility: visible; }
    body.dark-mode .cart-drawer { background: #1a1a2c; }
    .cart-form { display: flex; flex-direction: column; flex: 1; min-height: 0; }

    .cart-drawer-head { padding: .9rem 1.1rem; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #1a1a2e, #b5451b); color: #fff; flex-shrink: 0; }
    .cart-drawer-head h5 { margin: 0; font-weight: 800; font-size: 1rem; }
    .cart-n { opacity: .7; font-weight: 600; }
    .cart-close { background: rgba(255,255,255,.15); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; }
    .cart-vaciar { background: none; border: none; color: #fff; opacity: .75; font-size: .76rem; }
    .cart-vaciar:hover { opacity: 1; }

    .cart-drawer-body { flex: 1; overflow-y: auto; padding: .8rem 1.1rem; min-height: 120px; }
    .cart-buscar { position: relative; }
    .cart-buscar > i { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: .9rem; }
    .cart-buscar input { padding-left: 2.2rem; border-radius: 12px; height: 44px; font-size: .95rem; border: 1.5px solid #e9e4de; }
    .cart-buscar input:focus { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }
    .cart-ayuda { font-size: .7rem; color: #adb5bd; margin: .3rem .2rem .5rem; }
    .cart-ayuda b { color: #8a8a9d; font-weight: 700; }
    .cart-sug { position: absolute; left: 0; right: 0; top: calc(100% + 4px); background: #fff; border-radius: 14px; box-shadow: 0 12px 34px rgba(0,0,0,.18);
        z-index: 5; max-height: 360px; overflow-y: auto; display: none; padding: .3rem; }
    .cart-sug.show { display: block; }
    .cart-sug .cs-tit { font-size: .64rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #adb5bd; padding: .35rem .55rem .2rem; }
    .cart-sug .cs-op { display: grid; grid-template-columns: 34px minmax(0, 1fr) auto; gap: .6rem; align-items: center; width: 100%; text-align: left;
        border: 0; background: none; padding: .4rem .55rem; border-radius: 10px; cursor: pointer; color: inherit; }
    .cart-sug .cs-op.activo, .cart-sug .cs-op:hover { background: #fdf1ea; }
    .cart-sug .cs-op:disabled { opacity: .45; cursor: not-allowed; }
    .cart-sug .cs-ic { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .9rem; overflow: hidden;
        background: color-mix(in srgb, var(--c) 14%, #fff); color: var(--c); }
    .cart-sug .cs-ic img { width: 100%; height: 100%; object-fit: cover; }
    .cart-sug .cs-n { font-weight: 700; font-size: .87rem; color: #1a1a2e; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cart-sug .cs-n mark { background: #ffe8a3; color: inherit; padding: 0; border-radius: 3px; }
    .cart-sug .cs-sub { font-size: .7rem; color: #8a8a9d; }
    .cart-sug .cs-sub .ya { color: #1e8e5a; font-weight: 700; }
    .cart-sug .cs-sub .poco { color: #d4880f; font-weight: 700; }
    .cart-sug .cs-der { text-align: right; white-space: nowrap; }
    .cart-sug .cs-der b { display: block; color: #1e8e5a; font-size: .88rem; }
    .cart-sug .cs-der small { font-size: .66rem; color: #b5451b; font-weight: 700; }
    .cart-sug .cs-nada { padding: 1rem; text-align: center; color: #8a8a9d; font-size: .84rem; }
    body.dark-mode .cart-sug { background: #24243b; }
    body.dark-mode .cart-sug .cs-op.activo, body.dark-mode .cart-sug .cs-op:hover { background: #2f2f4a; }
    body.dark-mode .cart-sug .cs-n { color: #f0f0f7; }
    body.dark-mode .cart-sug .cs-ic { background: #2f2f4a; }
    body.dark-mode .cart-buscar input { border-color: #33334d; }
    .cart-rapidos { display: flex; gap: .35rem; overflow-x: auto; padding-bottom: .4rem; margin-bottom: .2rem; scrollbar-width: thin; }
    .cart-rapidos:empty { display: none; }
    .cart-rapidos button { flex-shrink: 0; border: 1.5px solid #eee5dd; background: #fff; border-radius: 20px; padding: .22rem .65rem; font-size: .74rem; font-weight: 700; color: #555; }
    .cart-rapidos button:hover:not(:disabled) { border-color: #b5451b; color: #b5451b; }
    .cart-rapidos button:disabled { opacity: .45; }
    .cart-rapidos button span { color: #1e8e5a; margin-left: .2rem; }
    body.dark-mode .cart-rapidos button { background: #24243b; border-color: #33334d; color: #ccc; }

    .cart-empty { text-align: center; padding: 2rem 1rem; color: #adb5bd; }
    .cart-empty i { font-size: 2rem; margin-bottom: .5rem; opacity: .5; }
    .cart-empty p { font-size: .85rem; margin: 0; }

    .cart-item { display: grid; grid-template-columns: minmax(0, 1fr) auto 70px; gap: .55rem; padding: .55rem 0; border-bottom: 1px dashed #eee5dd; align-items: center; }
    body.dark-mode .cart-item { border-bottom-color: #2c2c44; }
    .cart-item-name { font-weight: 700; font-size: .85rem; color: #1a1a2e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    body.dark-mode .cart-item-name { color: #f0f0f7; }
    .cart-item-price { font-size: .72rem; color: #8a8a9d; }
    .cart-item-price .poco { color: #d4880f; font-weight: 700; }
    .cart-item-tot { text-align: right; font-weight: 800; font-size: .88rem; white-space: nowrap; }
    .cart-qty { display: inline-flex; align-items: center; border: 1.5px solid #eee5dd; border-radius: 10px; overflow: hidden; }
    .cart-qty button { border: 0; background: transparent; width: 28px; height: 30px; color: #b5451b; font-weight: 800; }
    .cart-qty button:disabled { color: #ccc; }
    .cart-qty input { width: 38px; border: 0; text-align: center; font-weight: 800; font-size: .88rem; background: transparent; color: inherit; -moz-appearance: textfield; }
    .cart-qty input::-webkit-inner-spin-button { -webkit-appearance: none; }
    body.dark-mode .cart-qty { border-color: #33334d; }

    .cart-drawer-foot { padding: .8rem 1.1rem 1rem; border-top: 1px solid #f0f0f0; flex-shrink: 0; background: #fdfaf6; max-height: 62vh; overflow-y: auto; }
    body.dark-mode .cart-drawer-foot { border-top-color: #2c2c44; background: #20203a; }
    .cart-error { background: #fbe9e7; color: #a93226; border-radius: 10px; padding: .5rem .7rem; font-size: .82rem; margin-bottom: .6rem; }
    .cart-label { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .05em; color: #8a8a9d; text-transform: uppercase; margin: .55rem 0 .3rem; }
    .cart-fila2 .cart-label { margin-top: 0; }
    .cart-medios { display: grid; grid-template-columns: repeat(5, 1fr); gap: .3rem; }
    .cart-medios input { position: absolute; opacity: 0; pointer-events: none; }
    .cart-medios label { margin: 0; border: 1.5px solid #eee5dd; border-radius: 10px; padding: .4rem .1rem .3rem; text-align: center; cursor: pointer; font-size: .68rem; font-weight: 700; color: #555; background: #fff; }
    .cart-medios label i { display: block; font-size: .95rem; margin-bottom: .1rem; color: var(--c); }
    .cart-medios input:checked + label { border-color: var(--c); background: color-mix(in srgb, var(--c) 10%, #fff); color: #1a1a2e; }
    .cart-medios input:focus-visible + label { outline: 2px solid var(--c); outline-offset: 2px; }
    body.dark-mode .cart-medios label { background: #24243b; border-color: #33334d; color: #ccc; }
    body.dark-mode .cart-medios input:checked + label { background: #2f2f4a; color: #fff; }
    .cart-recibido { display: flex; align-items: center; gap: .6rem; margin-top: .6rem; }
    .cart-recibido .input-group { flex: 1; }
    .cart-billetes { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: .4rem; }
    .cart-billetes button { border: 1.5px solid #eee5dd; background: #fff; border-radius: 8px; font-size: .74rem; font-weight: 700; padding: .15rem .5rem; color: #555; }
    body.dark-mode .cart-billetes button { background: #24243b; border-color: #33334d; color: #ddd; }
    .cart-vuelto { display: flex; justify-content: space-between; margin-top: .45rem; padding: .4rem .7rem; border-radius: 10px; background: #e5f6ec; color: #1e8e5a; font-weight: 800; font-size: .9rem; }
    .cart-vuelto.falta { background: #fbe9e7; color: #c0392b; }
    #cartOperacion { margin-top: .6rem; }
    .cart-nota { margin-top: .5rem; font-size: .8rem; color: #8a8a9d; }
    .cart-totals { margin: .7rem 0 .6rem; }
    .ct-row { display: flex; justify-content: space-between; font-size: .8rem; padding: .1rem 0; }
    .ct-row.ct-muted { color: #8a8a9d; }
    .ct-row.ct-total { font-size: 1.35rem; font-weight: 900; color: #1a1a2e; border-top: 1px dashed #e9ecef; margin-top: .3rem; padding-top: .4rem; }
    body.dark-mode .ct-row.ct-total { color: #fff; border-top-color: #33334d; }
    .btn-cobrar { height: 48px; font-size: 1.02rem; font-weight: 800; border-radius: 12px; }
    body.dark-mode .cart-drawer-foot .form-control, body.dark-mode .cart-drawer-body .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }
    @media (max-width: 575px) {
        .cart-drawer { width: 100vw; }
        .cart-fab { right: 1rem; bottom: 1rem; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const STORAGE_KEY = 'carritoVentaRapida';
    const CATALOGO = @json($catalogoVenta);
    const VENTA_OK = @json((bool) $ultimaVenta);
    const HAY_ERROR = @json($errors->any());
    const VIEJOS = @json(old('productos', []));
    const porId = {}; CATALOGO.forEach(p => porId[p.id] = p);
    const $ = id => document.getElementById(id);
    const soles = n => 'S/ ' + (Math.round(n * 100) / 100).toFixed(2);
    const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const sinTildes = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    const avisar = t => window.toastr ? toastr.warning(t) : alert(t);

    // carrito: id → cantidad. Precio, nombre y stock salen siempre del catálogo actual (no se quedan viejos)
    let carrito = new Map();
    function leer() {
        let guardado = {};
        try { guardado = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}') || {}; } catch (e) {}
        Object.entries(guardado).forEach(([id, v]) => carrito.set(+id, typeof v === 'object' ? +v.cantidad : +v));
    }
    function guardar() {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(Object.fromEntries(carrito))); } catch (e) {}
    }

    if (VENTA_OK) { try { localStorage.removeItem(STORAGE_KEY); } catch (e) {} }   // la venta se registró: carrito limpio
    else if (HAY_ERROR && (Array.isArray(VIEJOS) ? VIEJOS : Object.values(VIEJOS)).length) {
        (Array.isArray(VIEJOS) ? VIEJOS : Object.values(VIEJOS)).forEach(l => carrito.set(+l.id_producto, +l.cantidad));
    } else leer();

    // Quitar lo que ya no se puede vender y ajustar al stock de ahora
    const ajustados = [];
    [...carrito].forEach(([id, c]) => {
        const p = porId[id];
        if (!p || p.stock <= 0 || !(c > 0)) { carrito.delete(id); if (p) ajustados.push(p.n); return; }
        if (c > p.stock) { carrito.set(id, p.stock); ajustados.push(p.n); }
    });
    if (ajustados.length) setTimeout(() => avisar('Se ajustó la venta por el stock actual: ' + ajustados.join(', ')), 400);

    function agregar(id, n = 1) {
        const p = porId[id]; if (!p) return;
        const actual = carrito.get(id) || 0;
        if (actual + n > p.stock) { avisar(`Solo hay ${p.stock} de ${p.n}.`); return; }
        carrito.set(id, actual + n); guardar(); render();
        const fab = $('cartFab'); fab.classList.remove('bump'); void fab.offsetWidth; fab.classList.add('bump');
    }
    function fijar(id, n) {
        const p = porId[id]; if (!p) return;
        n = parseInt(n, 10) || 0;
        if (n > p.stock) { avisar(`Solo hay ${p.stock} de ${p.n}.`); n = p.stock; }
        if (n <= 0) carrito.delete(id); else carrito.set(id, n);
        guardar(); render();
    }

    window.carritoAgregar = btn => agregar(+btn.dataset.id);
    window.carritoToggle = function (abrir) {
        const d = $('cartDrawer'), abierto = typeof abrir === 'boolean' ? abrir : !d.classList.contains('show');
        d.classList.toggle('show', abierto); $('cartOverlay').classList.toggle('show', abierto);
        if (!abierto) cerrarSug();
    };

    function totales() {
        let total = 0, uds = 0;
        carrito.forEach((c, id) => { total += Math.round(porId[id].precio * c * 100) / 100; uds += c; });
        total = Math.round(total * 100) / 100;
        const gravada = Math.round(total / 1.18 * 100) / 100;   // el precio ya trae el IGV
        return { total, gravada, igv: Math.round((total - gravada) * 100) / 100, uds };
    }

    function render() {
        const ids = [...carrito.keys()];
        $('cartItems').innerHTML = ids.length ? ids.map(id => {
            const p = porId[id], c = carrito.get(id), queda = p.stock - c;
            return `<div class="cart-item" data-id="${id}">
                <div style="min-width:0"><div class="cart-item-name" title="${esc(p.n)}">${esc(p.n)}</div>
                    <div class="cart-item-price">${soles(p.precio)} c/u${queda <= 3 ? ` · <span class="poco">${queda ? 'quedan ' + queda : 'sin más stock'}</span>` : ''}</div></div>
                <div class="cart-qty">
                    <button type="button" data-acc="-1" aria-label="Quitar uno">${c === 1 ? '<i class="fas fa-trash-alt" style="font-size:.72rem"></i>' : '−'}</button>
                    <input type="number" min="0" max="${p.stock}" value="${c}" inputmode="numeric" aria-label="Cantidad de ${esc(p.n)}">
                    <button type="button" data-acc="1" aria-label="Agregar uno" ${c >= p.stock ? 'disabled' : ''}>+</button>
                </div>
                <div class="cart-item-tot">${soles(p.precio * c)}</div>
            </div>`;
        }).join('') : '<div class="cart-empty"><i class="fas fa-shopping-basket"></i><p>Toca "Agregar" en un producto o búscalo arriba</p></div>';

        $('cartHiddenInputs').innerHTML = ids.map((id, i) =>
            `<input type="hidden" name="productos[${i}][id_producto]" value="${id}"><input type="hidden" name="productos[${i}][cantidad]" value="${carrito.get(id)}">`).join('');

        const t = totales();
        $('ctSubtotal').textContent = soles(t.gravada); $('ctIgv').textContent = soles(t.igv); $('ctTotal').textContent = soles(t.total);
        $('cartN').textContent = t.uds ? '· ' + t.uds : '';
        $('cartBadge').style.display = t.uds ? 'flex' : 'none'; $('cartBadge').textContent = t.uds;
        $('cartFabTotal').textContent = t.uds ? soles(t.total) : '';
        $('cartVaciar').style.visibility = t.uds ? 'visible' : 'hidden';
        $('btnCartSubmit').disabled = !t.uds;
        $('btnCartSubmit').innerHTML = '<i class="fas fa-check-circle mr-1"></i>Cobrar' + (t.uds ? ' ' + soles(t.total) : '');

        // Botones de las tarjetas: cuántos van en la venta
        document.querySelectorAll('.btn-cart-add[data-id]').forEach(b => {
            const c = carrito.get(+b.dataset.id) || 0;
            b.classList.toggle('en-venta', c > 0);
            b.innerHTML = c ? `<i class="fas fa-check mr-1"></i>${c} en la venta · agregar otro` : '<i class="fas fa-cart-plus mr-1"></i>Agregar a la venta';
        });
        billetes(t.total); vuelto();
        if (typeof rapidos === 'function' && $('cartRapidos')) rapidos();
    }

    $('cartItems').addEventListener('click', e => {
        const b = e.target.closest('button[data-acc]'); if (!b) return;
        const id = +b.closest('.cart-item').dataset.id;
        fijar(id, (carrito.get(id) || 0) + +b.dataset.acc);
    });
    $('cartItems').addEventListener('change', e => { if (e.target.matches('input')) fijar(+e.target.closest('.cart-item').dataset.id, e.target.value); });
    $('cartVaciar').addEventListener('click', () => { if (carrito.size && confirm('¿Vaciar la venta?')) { carrito.clear(); $('cartRecibido').value = ''; guardar(); render(); } });

    // ── Buscar dentro de la venta ──
    // Busca por palabras en cualquier orden, en el nombre o la categoría ("pan yema", "pastel").
    // Si empieza con un número agrega esa cantidad: "3 pan" o "3x pan".
    const MAS_VENDIDOS = CATALOGO.filter(p => p.v30 > 0).sort((a, b) => b.v30 - a.v30).slice(0, 8);
    let sugLista = [], sugActiva = -1;
    const disponible = p => p.stock - (carrito.get(p.id) || 0);
    function leerConsulta() {
        const crudo = $('cartBuscar').value.trim();
        const m = crudo.match(/^(\d{1,3})\s*[x*]?\s+(.*)$/i) || crudo.match(/^(\d{1,3})\s*[x*]\s*(.*)$/i);
        return m ? { cant: Math.max(1, +m[1]), q: m[2].trim() } : { cant: 1, q: crudo };
    }
    function resaltar(nombre, palabras) {
        let html = esc(nombre);
        palabras.forEach(w => {
            const base = sinTildes(nombre), i = base.indexOf(w);
            if (i < 0 || !w) return;
            const orig = nombre.substr(i, w.length);
            html = html.replace(esc(orig), '<mark>' + esc(orig) + '</mark>');
        });
        return html;
    }
    function buscar(q) {
        const palabras = sinTildes(q).split(/\s+/).filter(Boolean);
        if (!palabras.length) return { lista: MAS_VENDIDOS.length ? MAS_VENDIDOS : CATALOGO.slice(0, 8), palabras, titulo: MAS_VENDIDOS.length ? 'Más vendidos (30 días)' : 'Productos' };
        const lista = CATALOGO.map(p => {
            const n = sinTildes(p.n), c = sinTildes(p.cat);
            if (!palabras.every(w => n.includes(w) || c.includes(w))) return null;
            let pts = 0;
            if (n.startsWith(palabras[0])) pts += 100;
            if (n.split(/\s+/).some(t => t.startsWith(palabras[0]))) pts += 40;
            if (palabras.every(w => n.includes(w))) pts += 30;
            if (disponible(p) <= 0) pts -= 200;
            return { p, pts: pts + Math.min(p.v30, 500) / 50 };
        }).filter(Boolean).sort((a, b) => b.pts - a.pts).map(x => x.p).slice(0, 12);
        return { lista, palabras, titulo: lista.length ? lista.length + ' resultado' + (lista.length === 1 ? '' : 's') : '' };
    }
    function sugerir() {
        const { cant, q } = leerConsulta(), box = $('cartSug');
        const r = buscar(q);
        sugLista = r.lista;
        sugActiva = sugLista.findIndex(p => disponible(p) >= cant);
        box.innerHTML = (r.titulo ? `<div class="cs-tit">${r.titulo}${cant > 1 ? ' · agregar ' + cant : ''}</div>` : '') + (sugLista.length ? sugLista.map((p, i) => {
            const ya = carrito.get(p.id) || 0, libre = disponible(p);
            return `<button type="button" class="cs-op ${i === sugActiva ? 'activo' : ''}" data-i="${i}" role="option" ${libre < cant ? 'disabled' : ''}>
                <span class="cs-ic" style="--c:${esc(p.col)}">${p.img ? `<img src="${esc(p.img)}" alt="" loading="lazy">` : `<i class="fas ${esc(p.ic)}"></i>`}</span>
                <span style="min-width:0"><span class="cs-n">${resaltar(p.n, r.palabras)}</span>
                    <span class="cs-sub d-block">${esc(p.cat)} · ${p.stock <= 0 ? 'agotado' : (libre <= 0 ? '<span class="poco">ya no queda más</span>' : (p.stock <= 5 ? `<span class="poco">quedan ${p.stock}</span>` : p.stock + ' disp.'))}${ya ? ` · <span class="ya">${ya} en la venta</span>` : ''}</span></span>
                <span class="cs-der"><b>${soles(p.precio * cant)}</b>${cant > 1 ? `<small>${cant} × ${soles(p.precio)}</small>` : ''}</span>
            </button>`;
        }).join('') : `<div class="cs-nada"><i class="fas fa-search d-block mb-1" style="opacity:.4;font-size:1.3rem"></i>No hay productos activos con "${esc(q)}".</div>`);
        box.classList.add('show');
        $('cartBuscar').setAttribute('aria-expanded', 'true');
        marcarActiva();
    }
    function marcarActiva() {
        $('cartSug').querySelectorAll('.cs-op').forEach((b, i) => { b.classList.toggle('activo', i === sugActiva); if (i === sugActiva) b.scrollIntoView({ block: 'nearest' }); });
    }
    function cerrarSug() { $('cartSug').classList.remove('show'); $('cartBuscar').setAttribute('aria-expanded', 'false'); }
    function elegir(i) {
        const p = sugLista[i]; if (!p) return;
        const { cant } = leerConsulta();
        if (disponible(p) < cant) { avisar(`Solo puedes agregar ${Math.max(0, disponible(p))} más de ${p.n}.`); return; }
        agregar(p.id, cant);
        $('cartBuscar').value = ''; sugerir();
    }
    function rapidos() {
        $('cartRapidos').innerHTML = MAS_VENDIDOS.slice(0, 6).map(p =>
            `<button type="button" data-id="${p.id}" ${disponible(p) <= 0 ? 'disabled' : ''} title="Agregar 1 ${esc(p.n)}">+ ${esc(p.n)}<span>${soles(p.precio)}</span></button>`).join('');
    }
    $('cartRapidos').addEventListener('click', e => { const b = e.target.closest('button'); if (b) agregar(+b.dataset.id); });
    $('cartBuscar').addEventListener('input', sugerir);
    $('cartBuscar').addEventListener('focus', sugerir);
    $('cartBuscar').addEventListener('keydown', e => {
        const n = sugLista.length;
        if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && n) {
            e.preventDefault();
            if (!$('cartSug').classList.contains('show')) sugerir();
            let i = sugActiva;
            for (let k = 0; k < n; k++) {   // salta los que no se pueden agregar
                i = (i + (e.key === 'ArrowDown' ? 1 : -1) + n) % n;
                if (!$('cartSug').querySelectorAll('.cs-op')[i]?.disabled) break;
            }
            sugActiva = i; marcarActiva();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (sugActiva >= 0) elegir(sugActiva);
            else if (leerConsulta().q) avisar('No hay un producto disponible con ese nombre.');
        } else if (e.key === 'Escape') {
            e.stopPropagation();
            if ($('cartBuscar').value) { $('cartBuscar').value = ''; sugerir(); } else { cerrarSug(); $('cartBuscar').blur(); }
        }
    });
    $('cartSug').addEventListener('mousedown', e => {
        const b = e.target.closest('.cs-op'); if (!b || b.disabled) return;
        e.preventDefault(); elegir(+b.dataset.i); $('cartBuscar').focus();
    });
    $('cartBuscar').addEventListener('blur', () => setTimeout(cerrarSug, 150));

    // ── Pago ──
    const medio = () => (document.querySelector('#formCarrito input[name=tipo_pago]:checked') || {}).value;
    function pintarMedio() {
        const ef = medio() === 'efectivo';
        $('cartEfectivo').hidden = !ef; $('cartOperacion').hidden = ef;
    }
    document.querySelectorAll('#formCarrito input[name=tipo_pago]').forEach(r => r.addEventListener('change', pintarMedio));
    function billetes(total) {
        if (!total) { $('cartBilletes').innerHTML = ''; return; }
        const op = [total];
        [5, 10, 20, 50, 100, 200].forEach(b => { const v = Math.ceil(total / b) * b; if (v > total && !op.includes(v) && op.length < 5) op.push(v); });
        $('cartBilletes').innerHTML = op.map((v, i) => `<button type="button" data-v="${v}">${i ? soles(v) : 'Exacto'}</button>`).join('');
    }
    $('cartBilletes').addEventListener('click', e => { const b = e.target.closest('button'); if (b) { $('cartRecibido').value = (+b.dataset.v).toFixed(2); vuelto(); } });
    $('cartRecibido').addEventListener('input', vuelto);
    function vuelto() {
        const v = $('cartRecibido').value, t = totales().total, box = $('cartVuelto');
        if (v === '' || !t) { box.hidden = true; return true; }
        const dif = Math.round((+v - t) * 100) / 100;
        box.hidden = false; box.classList.toggle('falta', dif < 0);
        $('cartVueltoTxt').textContent = dif < 0 ? 'Falta' : 'Vuelto';
        $('cartVueltoMonto').textContent = soles(Math.abs(dif));
        return dif >= 0;
    }

    $('formCarrito').addEventListener('submit', e => {
        if (!carrito.size) { e.preventDefault(); return; }
        if (medio() === 'efectivo' && !vuelto()) { e.preventDefault(); avisar('Lo recibido no alcanza para el total.'); $('cartRecibido').focus(); return; }
        $('btnCartSubmit').disabled = true; $('btnCartSubmit').innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Registrando…';
    });

    // Atajos: F2 abre y busca, F9 cobra, Esc cierra
    document.addEventListener('keydown', e => {
        const abierto = $('cartDrawer').classList.contains('show');
        if (e.key === 'F2') { e.preventDefault(); carritoToggle(true); setTimeout(() => $('cartBuscar').focus(), 260); }
        else if (e.key === 'F9' && abierto && carrito.size) { e.preventDefault(); $('formCarrito').requestSubmit(); }
        else if (e.key === 'Escape' && abierto) carritoToggle(false);
    });

    guardar(); pintarMedio(); render();
    if (HAY_ERROR) carritoToggle(true);
})();
</script>
@endpush
@endif
