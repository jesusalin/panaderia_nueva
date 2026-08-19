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

                @if($puedeVender)
                    @if($p->estado === 'activo' && $p->stock_actual > 0)
                        <button type="button" class="btn btn-cart-add"
                            data-id="{{ $p->id }}" data-nombre="{{ $p->nombre }}"
                            data-precio="{{ $p->precio_venta }}" data-stock="{{ $p->stock_actual }}"
                            onclick="carritoAgregar(this)">
                            <i class="fas fa-cart-plus mr-1"></i>Agregar al carrito
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

@if($puedeVender)
{{-- ══════════ CARRITO FLOTANTE DE COMPRA RÁPIDA ══════════ --}}
<button type="button" class="cart-fab" id="cartFab" onclick="carritoToggle()">
    <i class="fas fa-shopping-cart"></i>
    <span class="cart-fab-badge" id="cartBadge" style="display:none;">0</span>
</button>

<div class="cart-overlay" id="cartOverlay" onclick="carritoToggle()"></div>

<div class="cart-drawer" id="cartDrawer">
    <div class="cart-drawer-head">
        <h5><i class="fas fa-shopping-basket mr-2"></i>Venta rápida</h5>
        <button type="button" class="cart-close" onclick="carritoToggle()"><i class="fas fa-times"></i></button>
    </div>

    <div class="cart-drawer-body" id="cartItems">
        <div class="cart-empty" id="cartEmpty">
            <i class="fas fa-shopping-basket"></i>
            <p>Agrega productos desde el catálogo</p>
        </div>
    </div>

    <form action="{{ route('ventas.store') }}" method="POST" id="formCarrito" class="cart-drawer-foot">
        @csrf
        <div class="cart-totals">
            <div class="ct-row"><span>Subtotal</span><span id="ctSubtotal">S/ 0.00</span></div>
            <div class="ct-row ct-muted"><span>IGV (18%)</span><span id="ctIgv">S/ 0.00</span></div>
            <div class="ct-row ct-total"><span>Total</span><span id="ctTotal">S/ 0.00</span></div>
        </div>

        <div class="form-group mb-2">
            <label class="cart-label">Cliente</label>
            <select name="id_cliente" class="form-control form-control-sm">
                @forelse($clientes as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                @empty
                    <option value="">General</option>
                @endforelse
            </select>
        </div>
        <div class="form-group mb-3">
            <label class="cart-label">Tipo de pago</label>
            <select name="tipo_pago" class="form-control form-control-sm">
                <option value="efectivo">💵 Efectivo</option>
                <option value="yape">📱 Yape</option>
                <option value="plin">📱 Plin</option>
                <option value="tarjeta">💳 Tarjeta</option>
                <option value="otro">Otro</option>
            </select>
        </div>

        <div id="cartHiddenInputs"></div>

        <button type="submit" class="btn btn-success btn-block" id="btnCartSubmit" disabled>
            <i class="fas fa-check-circle mr-1"></i>Registrar venta
        </button>
    </form>
</div>
@endif

@endsection

@if($puedeVender)
@push('styles')
<style>
    .btn-cart-add {
        width: 100%; margin-top: .7rem; border: none; border-radius: 9px; padding: .5rem;
        background: rgba(181,69,27,.1); color: #b5451b; font-weight: 700; font-size: .82rem; transition: all .15s;
    }
    .btn-cart-add:hover:not(:disabled) { background: #b5451b; color: #fff; }
    .btn-cart-add:disabled { background: #f1f1f4; color: #adb5bd; cursor: not-allowed; }
    body.dark-mode .btn-cart-add { background: rgba(181,69,27,.22); color: #ff9d6e; }
    body.dark-mode .btn-cart-add:hover:not(:disabled) { background: #b5451b; color: #fff; }
    body.dark-mode .btn-cart-add:disabled { background: #2c2c44; color: #5c5c78; }

    .cart-fab {
        position: fixed; right: 1.75rem; bottom: 1.75rem; width: 58px; height: 58px; border-radius: 50%;
        background: linear-gradient(135deg, #1a1a2e, #b5451b); color: #fff; border: none; font-size: 1.3rem;
        box-shadow: 0 8px 22px rgba(181,69,27,.4); z-index: 1040; cursor: pointer; transition: transform .15s;
    }
    .cart-fab:hover { transform: scale(1.07); }
    .cart-fab-badge {
        position: absolute; top: -4px; right: -4px; background: #e74c3c; color: #fff; font-size: .68rem; font-weight: 800;
        min-width: 20px; height: 20px; border-radius: 10px; display: flex; align-items: center; justify-content: center; padding: 0 4px;
        border: 2px solid #fff;
    }
    .cart-fab.bump { animation: cartBump .3s ease; }
    @keyframes cartBump { 0%,100% { transform: scale(1); } 40% { transform: scale(1.18); } }

    .cart-overlay {
        position: fixed; inset: 0; background: rgba(10,10,20,.45); z-index: 1049;
        opacity: 0; pointer-events: none; transition: opacity .2s;
    }
    .cart-overlay.show { opacity: 1; pointer-events: auto; }

    .cart-drawer {
        position: fixed; top: 0; right: -380px; width: 360px; max-width: 90vw; height: 100vh; background: #fff;
        z-index: 1050; box-shadow: -8px 0 30px rgba(0,0,0,.15); display: flex; flex-direction: column; transition: right .25s ease;
    }
    .cart-drawer.show { right: 0; }
    body.dark-mode .cart-drawer { background: #1a1a2c; }

    .cart-drawer-head {
        padding: 1.1rem 1.3rem; display: flex; align-items: center; justify-content: space-between;
        background: linear-gradient(135deg, #1a1a2e, #b5451b); color: #fff; flex-shrink: 0;
    }
    .cart-drawer-head h5 { margin: 0; font-weight: 800; font-size: 1rem; }
    .cart-close { background: rgba(255,255,255,.15); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; }

    .cart-drawer-body { flex: 1; overflow-y: auto; padding: 1rem 1.3rem; }
    .cart-empty { text-align: center; padding: 3rem 1rem; color: #adb5bd; }
    .cart-empty i { font-size: 2.2rem; margin-bottom: .6rem; opacity: .5; }
    .cart-empty p { font-size: .85rem; margin: 0; }

    .cart-item { display: flex; gap: .7rem; padding: .7rem 0; border-bottom: 1px solid #f2f2f2; align-items: center; }
    body.dark-mode .cart-item { border-bottom-color: #2c2c44; }
    .cart-item-info { flex: 1; min-width: 0; }
    .cart-item-name { font-weight: 700; font-size: .82rem; color: #1a1a2e; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    body.dark-mode .cart-item-name { color: #f0f0f7; }
    .cart-item-price { font-size: .74rem; color: #8a8a9d; }
    .cart-item-qty { display: flex; align-items: center; gap: .35rem; }
    .cart-item-qty button {
        width: 22px; height: 22px; border-radius: 6px; border: 1px solid #e9ecef; background: #fff; font-size: .72rem; color: #495057;
    }
    body.dark-mode .cart-item-qty button { background: #24243b; border-color: #33334d; color: #d5d5e2; }
    .cart-item-qty span { font-size: .8rem; font-weight: 700; min-width: 16px; text-align: center; }
    .cart-item-remove { background: none; border: none; color: #e74c3c; font-size: .82rem; padding: 0 0 0 .5rem; }

    .cart-drawer-foot { padding: 1rem 1.3rem 1.3rem; border-top: 1px solid #f0f0f0; flex-shrink: 0; }
    body.dark-mode .cart-drawer-foot { border-top-color: #2c2c44; }
    .cart-totals { margin-bottom: .8rem; }
    .ct-row { display: flex; justify-content: space-between; font-size: .82rem; padding: .15rem 0; }
    .ct-row.ct-muted { color: #8a8a9d; }
    .ct-row.ct-total { font-size: 1.05rem; font-weight: 800; color: #1e8e5a; border-top: 1px dashed #e9ecef; margin-top: .3rem; padding-top: .5rem; }
    body.dark-mode .ct-row.ct-total { color: #6ee7a5; border-top-color: #33334d; }
    .cart-label { font-size: .74rem; font-weight: 700; color: #8a8a9d; text-transform: uppercase; margin-bottom: .25rem; }
    body.dark-mode .cart-drawer-foot .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const STORAGE_KEY = 'carritoVentaRapida';
    let carrito = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');

    window.carritoAgregar = function (btn) {
        const id = btn.dataset.id;
        const nombre = btn.dataset.nombre;
        const precio = parseFloat(btn.dataset.precio);
        const stock = parseInt(btn.dataset.stock);

        if (carrito[id]) {
            if (carrito[id].cantidad >= stock) { alert('No hay más stock disponible de este producto.'); return; }
            carrito[id].cantidad++;
        } else {
            carrito[id] = { id, nombre, precio, stock, cantidad: 1 };
        }
        guardar();
        render();

        const fab = document.getElementById('cartFab');
        fab.classList.remove('bump'); void fab.offsetWidth; fab.classList.add('bump');
    };

    window.carritoCambiarCant = function (id, delta) {
        if (!carrito[id]) return;
        carrito[id].cantidad += delta;
        if (carrito[id].cantidad <= 0) delete carrito[id];
        else if (carrito[id].cantidad > carrito[id].stock) carrito[id].cantidad = carrito[id].stock;
        guardar();
        render();
    };

    window.carritoQuitar = function (id) {
        delete carrito[id];
        guardar();
        render();
    };

    window.carritoToggle = function () {
        document.getElementById('cartDrawer').classList.toggle('show');
        document.getElementById('cartOverlay').classList.toggle('show');
    };

    function guardar() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(carrito));
    }

    function render() {
        const items = Object.values(carrito);
        const cont = document.getElementById('cartItems');
        const hidden = document.getElementById('cartHiddenInputs');
        const badge = document.getElementById('cartBadge');
        const btn = document.getElementById('btnCartSubmit');

        const totalUnidades = items.reduce((s, it) => s + it.cantidad, 0);
        badge.style.display = totalUnidades > 0 ? 'flex' : 'none';
        badge.textContent = totalUnidades;
        btn.disabled = items.length === 0;

        if (items.length === 0) {
            cont.innerHTML = '<div class="cart-empty" id="cartEmpty"><i class="fas fa-shopping-basket"></i><p>Agrega productos desde el catálogo</p></div>';
        } else {
            cont.innerHTML = items.map(it => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${it.nombre}</div>
                        <div class="cart-item-price">S/ ${it.precio.toFixed(2)} c/u</div>
                    </div>
                    <div class="cart-item-qty">
                        <button type="button" onclick="carritoCambiarCant('${it.id}', -1)">−</button>
                        <span>${it.cantidad}</span>
                        <button type="button" onclick="carritoCambiarCant('${it.id}', 1)">+</button>
                    </div>
                    <button type="button" class="cart-item-remove" onclick="carritoQuitar('${it.id}')"><i class="fas fa-trash"></i></button>
                </div>
            `).join('');
        }

        hidden.innerHTML = items.map((it, i) => `
            <input type="hidden" name="productos[${i}][id_producto]" value="${it.id}">
            <input type="hidden" name="productos[${i}][cantidad]" value="${it.cantidad}">
        `).join('');

        const subtotal = items.reduce((s, it) => s + it.precio * it.cantidad, 0);
        const igv = subtotal * 0.18;
        document.getElementById('ctSubtotal').textContent = 'S/ ' + subtotal.toFixed(2);
        document.getElementById('ctIgv').textContent = 'S/ ' + igv.toFixed(2);
        document.getElementById('ctTotal').textContent = 'S/ ' + (subtotal + igv).toFixed(2);
    }

    document.getElementById('formCarrito').addEventListener('submit', function () {
        localStorage.removeItem(STORAGE_KEY);
    });

    render();
})();
</script>
@endpush
@endif
