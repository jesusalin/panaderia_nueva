@extends('layouts.app')
@section('title', 'Nueva Venta')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ventas.index') }}">Ventas</a></li>
    <li class="breadcrumb-item active">Nueva Venta</li>
@endsection

@push('styles')
<style>
    .pos-card { border: none; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); overflow: hidden; }
    .pos-header { background: #fff; border-bottom: 1px solid #f0f0f0; padding: 1.1rem 1.4rem; display: flex; align-items: center; gap: .7rem; }
    .pos-header .ph-icon { width: 38px; height: 38px; border-radius: 10px; background: rgba(30,142,90,.1); color: #1e8e5a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pos-header h5 { margin: 0; font-weight: 800; color: #1a1a2e; font-size: .98rem; }
    body.dark-mode .pos-header { background: #1f1f33; border-bottom-color: #33334d; }
    body.dark-mode .pos-header h5 { color: #f0f0f7; }

    #buscador { border-radius: 10px; border: 1.5px solid #e9ecef; }
    #buscador:focus { border-color: #1e8e5a; box-shadow: 0 0 0 3px rgba(30,142,90,.12); }
    body.dark-mode #buscador { background: #24243b; border-color: #33334d; color: #e4e4ef; }

    .producto-item {
        border-radius: 12px !important; border: 1.5px solid #f0f0f0 !important; transition: border-color .15s ease, box-shadow .15s ease, transform .1s ease;
    }
    .producto-item:hover { border-color: #b5451b !important; box-shadow: 0 4px 15px rgba(181,69,27,.15); transform: translateY(-2px); }
    body.dark-mode .producto-item { background: #24243b; border-color: #33334d !important; }
    .prod-item-icon {
        width: 46px; height: 46px; border-radius: 12px; margin: 0 auto .5rem; display: flex; align-items: center; justify-content: center;
        background: rgba(181,69,27,.08); color: #b5451b; font-size: 1.25rem; overflow: hidden;
    }
    .prod-item-icon img { width: 100%; height: 100%; object-fit: cover; }
    .prod-item-icon .prod-item-emoji { font-size: 1.5rem; line-height: 1; }
    .prod-item-precio { color: #1e8e5a; }
    .prod-item-stock { font-size: .72rem; }

    .cart-card { position: sticky; top: 70px; }
    .cart-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #1e8e5a 100%);
        color: #fff; padding: 1.1rem 1.4rem; display: flex; align-items: center; gap: .6rem;
    }
    .cart-header h5 { margin: 0; font-weight: 800; font-size: 1rem; }
    #tablaCarrito thead th { background: #f7f5f3; border-top: none; font-size: .74rem; text-transform: uppercase; letter-spacing: .03em; color: #8a8a9d; }
    body.dark-mode #tablaCarrito thead th { background: #24243b; color: #9a9ac0; }
    body.dark-mode #tablaCarrito { color: #e4e4ef; }

    .cart-footer { background: #fdfaf6; border-top: 1px solid #f0f0f0; padding: 1.25rem 1.4rem; }
    body.dark-mode .cart-footer { background: #24243b; border-top-color: #33334d; }
    .cart-footer .form-control { border-radius: 10px; }
    body.dark-mode .cart-footer .form-control { background: #1f1f33; border-color: #33334d; color: #e4e4ef; }

    .cart-item-thumb {
        width: 26px; height: 26px; border-radius: 7px; flex-shrink: 0; object-fit: cover;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .cart-item-thumb.cart-item-emoji { background: rgba(181,69,27,.08); font-size: .9rem; }
</style>
@endpush

@section('content')
<form action="{{ route('ventas.store') }}" method="POST" id="formVenta" onsubmit="TiempoOperacion.registrarFin('registro_venta')">
@csrf
<div class="row">
    {{-- Panel izquierdo: productos --}}
    <div class="col-md-8">
        <div class="card pos-card">
            <div class="pos-header">
                <div class="ph-icon"><i class="fas fa-search"></i></div>
                <h5>Buscar Producto</h5>
            </div>
            <div class="card-body">
                <input type="text" id="buscador" class="form-control form-control-lg mb-3"
                    placeholder="Escriba el nombre del producto...">
                <div class="row" id="listaProductos">
                    @foreach($productos as $p)
                    @php
                        $catLower = mb_strtolower($p->categoria->nombre ?? '');
                        $emoji = '🍞';
                        if (str_contains($catLower, 'pastel') || str_contains($catLower, 'torta')) $emoji = '🎂';
                        elseif (str_contains($catLower, 'galleta'))  $emoji = '🍪';
                        elseif (str_contains($catLower, 'empanada')) $emoji = '🥟';
                        elseif (str_contains($catLower, 'bebida') || str_contains($catLower, 'café')) $emoji = '☕';
                    @endphp
                    <div class="col-md-4 col-6 producto-card mb-3" data-nombre="{{ strtolower($p->nombre) }}">
                        <div class="card h-100 cursor-pointer producto-item"
                            data-id="{{ $p->id }}"
                            data-nombre="{{ $p->nombre }}"
                            data-precio="{{ $p->precio_venta }}"
                            data-stock="{{ $p->stock_actual }}"
                            data-emoji="{{ $emoji }}"
                            data-imagen="{{ $p->imagen ? asset('storage/'.$p->imagen) : '' }}"
                            style="cursor:pointer;">
                            <div class="card-body text-center p-2">
                                <div class="prod-item-icon">
                                    @if($p->imagen)
                                        <img src="{{ asset('storage/'.$p->imagen) }}" alt="{{ $p->nombre }}">
                                    @else
                                        <span class="prod-item-emoji">{{ $emoji }}</span>
                                    @endif
                                </div>
                                <p class="mb-1 font-weight-bold small">{{ $p->nombre }}</p>
                                <p class="prod-item-precio font-weight-bold mb-1">S/ {{ number_format($p->precio_venta, 2) }}</p>
                                <small class="text-muted prod-item-stock">Stock: {{ $p->stock_actual }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Panel derecho: carrito --}}
    <div class="col-md-4">
        <div class="card pos-card cart-card">
            <div class="cart-header">
                <i class="fas fa-shopping-basket"></i>
                <h5>Carrito</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" id="tablaCarrito">
                    <thead class="bg-light">
                        <tr><th>Producto</th><th>Cant.</th><th>Subtotal</th><th></th></tr>
                    </thead>
                    <tbody id="carritoBody">
                        <tr id="carritoVacio"><td colspan="4" class="text-center text-muted py-3">Sin productos</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer cart-footer">
                <div class="d-flex justify-content-between mb-1">
                    <span>Subtotal:</span><span id="resSubtotal">S/ 0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-1 text-muted small">
                    <span>IGV (18%):</span><span id="resIgv">S/ 0.00</span>
                </div>
                <div class="d-flex justify-content-between mb-3 font-weight-bold h5">
                    <span>Total:</span><span id="resTotal" class="text-success">S/ 0.00</span>
                </div>

                <div class="form-group">
                    <label>Cliente</label>
                    <select name="id_cliente" class="form-control">
                        @foreach($clientes as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo de pago</label>
                    <select name="tipo_pago" class="form-control">
                        <option value="efectivo">💵 Efectivo</option>
                        <option value="yape">📱 Yape</option>
                        <option value="plin">📱 Plin</option>
                        <option value="tarjeta">💳 Tarjeta</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div id="hiddenProductos"></div>

                <button type="submit" class="btn btn-success btn-block btn-lg" id="btnVender" disabled>
                    <i class="fas fa-check-circle mr-2"></i>Registrar Venta
                </button>
                <a href="{{ route('ventas.index') }}" class="btn btn-light btn-block mt-2">Cancelar</a>
            </div>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
let carrito = {};
let hayBusquedaActiva = false;

// Cronometraje: registro de venta (desde que se carga la pantalla)
TiempoOperacion.reiniciar('registro_venta');

// Buscador
document.getElementById('buscador').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    if (q.length > 0) {
        TiempoOperacion.marcarInicio('busqueda_producto');
        hayBusquedaActiva = true;
    } else {
        hayBusquedaActiva = false;
    }
    document.querySelectorAll('.producto-card').forEach(el => {
        el.style.display = el.dataset.nombre.includes(q) ? '' : 'none';
    });
});

// Click en producto
document.querySelectorAll('.producto-item').forEach(el => {
    el.addEventListener('click', function() {
        const id     = this.dataset.id;
        const nombre = this.dataset.nombre;
        const precio = parseFloat(this.dataset.precio);
        const stock  = parseInt(this.dataset.stock);
        const emoji  = this.dataset.emoji || '🍞';
        const imagen = this.dataset.imagen || '';

        // Si el producto fue encontrado mediante el buscador, registrar cuánto tardó
        if (hayBusquedaActiva) {
            TiempoOperacion.registrarFin('busqueda_producto', id);
            hayBusquedaActiva = false;
        }

        if (carrito[id]) {
            if (carrito[id].cantidad >= stock) {
                alert('No hay suficiente stock.');
                return;
            }
            carrito[id].cantidad++;
        } else {
            carrito[id] = { id, nombre, precio, stock, cantidad: 1, emoji, imagen };
        }
        renderCarrito();
    });
});

function renderCarrito() {
    const tbody = document.getElementById('carritoBody');
    const hidden = document.getElementById('hiddenProductos');
    tbody.innerHTML = '';
    hidden.innerHTML = '';

    let subtotal = 0;
    const items = Object.values(carrito);

    if (items.length === 0) {
        tbody.innerHTML = '<tr id="carritoVacio"><td colspan="4" class="text-center text-muted py-3">Sin productos</td></tr>';
        document.getElementById('btnVender').disabled = true;
    } else {
        document.getElementById('btnVender').disabled = false;
        items.forEach((item, i) => {
            const sub = item.precio * item.cantidad;
            subtotal += sub;

            const tr = document.createElement('tr');
            const miniatura = item.imagen
                ? `<img src="${item.imagen}" alt="" class="cart-item-thumb">`
                : `<span class="cart-item-thumb cart-item-emoji">${item.emoji}</span>`;
            tr.innerHTML = `
                <td class="small">
                    <div class="d-flex align-items-center gap-2">
                        ${miniatura}
                        <span>${item.nombre}</span>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm" style="width:80px">
                        <div class="input-group-prepend"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="cambiarCant('${item.id}', -1)">-</button></div>
                        <input type="text" class="form-control text-center" value="${item.cantidad}" readonly>
                        <div class="input-group-append"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="cambiarCant('${item.id}', 1)">+</button></div>
                    </div>
                </td>
                <td class="small">S/ ${sub.toFixed(2)}</td>
                <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="quitarItem('${item.id}')"><i class="fas fa-times"></i></button></td>
            `;
            tbody.appendChild(tr);

            // Campos ocultos
            hidden.innerHTML += `<input type="hidden" name="productos[${i}][id_producto]" value="${item.id}">`;
            hidden.innerHTML += `<input type="hidden" name="productos[${i}][cantidad]" value="${item.cantidad}">`;
        });
    }

    const igv   = subtotal * 0.18;
    const total = subtotal + igv;
    document.getElementById('resSubtotal').textContent = 'S/ ' + subtotal.toFixed(2);
    document.getElementById('resIgv').textContent      = 'S/ ' + igv.toFixed(2);
    document.getElementById('resTotal').textContent    = 'S/ ' + total.toFixed(2);
}

function cambiarCant(id, delta) {
    if (!carrito[id]) return;
    carrito[id].cantidad += delta;
    if (carrito[id].cantidad <= 0) delete carrito[id];
    else if (carrito[id].cantidad > carrito[id].stock) carrito[id].cantidad = carrito[id].stock;
    renderCarrito();
}

function quitarItem(id) {
    delete carrito[id];
    renderCarrito();
}
</script>
@endpush
