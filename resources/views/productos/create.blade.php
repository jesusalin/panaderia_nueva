@extends('layouts.app')
@section('title', 'Nuevo Producto')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@include('productos.partials._styles')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card prod-form-card">
            <div class="prod-form-header">
                <div class="prod-form-icon"><i class="fas fa-bread-slice"></i></div>
                <div>
                    <h5>Nuevo Producto</h5>
                    <p>Se agregará al catálogo de productos que vendes</p>
                </div>
            </div>
            <div class="prod-form-body">
                <form action="{{ route('productos.store') }}" method="POST" enctype="multipart/form-data" id="formProducto">
                    @csrf

                    <label class="section-label"><i class="fas fa-info-circle mr-1"></i> Información básica</label>
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="nombre" id="inNombre" class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre') }}" placeholder="Ej: Pan Francés" required>
                            @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Categoría <span class="text-danger">*</span></label>
                            <select name="id_categoria" id="inCategoria" class="form-control @error('id_categoria') is-invalid @enderror" required>
                                <option value="">-- Seleccionar --</option>
                                @foreach($categorias as $c)
                                    <option value="{{ $c->id }}" {{ old('id_categoria') == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                                @endforeach
                            </select>
                            @error('id_categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripción <small class="text-muted">(opcional)</small></label>
                        <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción opcional...">{{ old('descripcion') }}</textarea>
                    </div>

                    <label class="section-label"><i class="fas fa-image mr-1"></i> Foto del producto</label>
                    <label class="img-upload d-block mb-0">
                        <input type="file" name="imagen" id="inImagen" accept="image/*">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="iu-text">Haz clic o arrastra una imagen (opcional, máx. 2MB)</div>
                    </label>

                    <label class="section-label"><i class="fas fa-tags mr-1"></i> Precio y stock</label>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Precio de Venta <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                                <input type="number" name="precio_venta" id="inPrecio" step="0.01" min="0"
                                    class="form-control @error('precio_venta') is-invalid @enderror"
                                    value="{{ old('precio_venta', '0.00') }}" required>
                            </div>
                            @error('precio_venta')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Costo de Producción</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                                <input type="number" name="costo_produccion" id="inCosto" step="0.01" min="0"
                                    class="form-control" value="{{ old('costo_produccion', '0.00') }}">
                            </div>
                        </div>
                    </div>

                    <div class="ganancia-box">
                        <span class="g-label"><i class="fas fa-coins mr-1"></i>Ganancia estimada por unidad</span>
                        <span class="g-value" id="gananciaValor">S/ 0.00</span>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6 form-group">
                            <label>Stock Actual <span class="text-danger">*</span></label>
                            <input type="number" name="stock_actual" id="inStock" min="0"
                                class="form-control @error('stock_actual') is-invalid @enderror"
                                value="{{ old('stock_actual', 0) }}" required>
                            @error('stock_actual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Stock Mínimo <span class="text-danger">*</span></label>
                            <input type="number" name="stock_minimo" id="inStockMin" min="0"
                                class="form-control @error('stock_minimo') is-invalid @enderror"
                                value="{{ old('stock_minimo', 0) }}" required>
                            @error('stock_minimo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Estado</label>
                        <select name="estado" id="inEstado" class="form-control">
                            <option value="activo" {{ old('estado','activo') === 'activo' ? 'selected' : '' }}>Activo</option>
                            <option value="inactivo" {{ old('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('productos.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Vista previa en vivo: así se va a ver en el catálogo --}}
    <div class="col-lg-4">
        <div class="preview-wrap">
            <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así se verá en el catálogo</span>
            <div class="item-card">
                <div class="item-card-media" id="prevMedia"><i class="fas fa-bread-slice" id="prevIcon"></i></div>
                <div class="item-card-body">
                    <div class="item-card-cat" id="prevCat">Categoría</div>
                    <h3 class="item-card-title" id="prevNombre">Nombre del producto</h3>
                    <div class="item-card-price">
                        S/ <span id="prevPrecio">0.00</span>
                        <span class="ic-cost" id="prevCostoWrap">costo S/ <span id="prevCosto">0.00</span></span>
                    </div>
                    <div class="item-card-stockrow">
                        <span>Stock disponible</span>
                        <span class="badge-soft badge-soft-success" id="prevStock">0 uds</span>
                    </div>
                </div>
                <div class="item-card-footer">
                    <span class="badge-soft badge-soft-success" id="prevEstado">Activo</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const $ = id => document.getElementById(id);

    function actualizarGanancia() {
        const precio = parseFloat($('inPrecio').value) || 0;
        const costo  = parseFloat($('inCosto').value) || 0;
        const gan = precio - costo;
        const el = $('gananciaValor');
        el.textContent = 'S/ ' + gan.toFixed(2);
        el.className = 'g-value ' + (gan >= 0 ? 'positivo' : 'negativo');
    }

    function actualizarPreview() {
        $('prevNombre').textContent = $('inNombre').value.trim() || 'Nombre del producto';
        const catSel = $('inCategoria');
        $('prevCat').textContent = catSel.value ? catSel.options[catSel.selectedIndex].text : 'Categoría';
        $('prevPrecio').textContent = (parseFloat($('inPrecio').value) || 0).toFixed(2);

        const costo = parseFloat($('inCosto').value) || 0;
        $('prevCosto').textContent = costo.toFixed(2);
        $('prevCostoWrap').style.display = costo > 0 ? 'inline' : 'none';

        const stock = parseInt($('inStock').value) || 0;
        const stockMin = parseInt($('inStockMin').value) || 0;
        const stockBajo = stock <= stockMin;
        $('prevStock').textContent = stock + ' uds';
        $('prevStock').className = 'badge-soft ' + (stockBajo ? 'badge-soft-danger' : 'badge-soft-success');

        const activo = $('inEstado').value === 'activo';
        $('prevEstado').textContent = activo ? 'Activo' : 'Inactivo';
        $('prevEstado').className = 'badge-soft ' + (activo ? 'badge-soft-success' : 'badge-soft-secondary');
    }

    ['inNombre','inCategoria','inPrecio','inCosto','inStock','inStockMin','inEstado'].forEach(id => {
        $(id).addEventListener('input', () => { actualizarGanancia(); actualizarPreview(); });
        $(id).addEventListener('change', () => { actualizarGanancia(); actualizarPreview(); });
    });

    // Vista previa de la imagen subida
    $('inImagen').addEventListener('change', function () {
        const file = this.files[0];
        const media = $('prevMedia');
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => { media.innerHTML = '<img src="' + e.target.result + '" alt="preview">'; };
        reader.readAsDataURL(file);
    });

    actualizarGanancia();
    actualizarPreview();
</script>
@endpush
