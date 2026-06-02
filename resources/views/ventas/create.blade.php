@extends('layouts.app')
@section('title', 'Nueva Venta')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ventas.index') }}">Ventas</a></li>
    <li class="breadcrumb-item active">Nueva Venta</li>
@endsection

@section('content')
<form action="{{ route('ventas.store') }}" method="POST" id="formVenta">
@csrf
<div class="row">
    {{-- Panel izquierdo: productos --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-search mr-2"></i>Buscar Producto</h5>
            </div>
            <div class="card-body">
                <input type="text" id="buscador" class="form-control form-control-lg mb-3"
                    placeholder="Escriba el nombre del producto...">
                <div class="row" id="listaProductos">
                    @foreach($productos as $p)
                    <div class="col-md-4 col-6 producto-card mb-3" data-nombre="{{ strtolower($p->nombre) }}">
                        <div class="card h-100 border cursor-pointer producto-item"
                            data-id="{{ $p->id }}"
                            data-nombre="{{ $p->nombre }}"
                            data-precio="{{ $p->precio_venta }}"
                            data-stock="{{ $p->stock_actual }}"
                            style="cursor:pointer; transition:.2s;"
                            onmouseover="this.style.boxShadow='0 4px 15px rgba(181,69,27,.3)'"
                            onmouseout="this.style.boxShadow=''">
                            <div class="card-body text-center p-2">
                                <div class="mb-1" style="font-size:2rem;">🍞</div>
                                <p class="mb-1 font-weight-bold small">{{ $p->nombre }}</p>
                                <p class="text-success font-weight-bold mb-1">S/ {{ number_format($p->precio_venta, 2) }}</p>
                                <small class="text-muted">Stock: {{ $p->stock_actual }}</small>
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
        <div class="card sticky-top" style="top: 70px;">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-shopping-basket mr-2"></i>Carrito</h5>
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
            <div class="card-footer">
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

// Buscador
document.getElementById('buscador').addEventListener('input', function() {
    const q = this.value.toLowerCase();
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

        if (carrito[id]) {
            if (carrito[id].cantidad >= stock) {
                alert('No hay suficiente stock.');
                return;
            }
            carrito[id].cantidad++;
        } else {
            carrito[id] = { id, nombre, precio, stock, cantidad: 1 };
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
            tr.innerHTML = `
                <td class="small">${item.nombre}</td>
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
