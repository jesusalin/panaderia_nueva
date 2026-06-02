@extends('layouts.app')
@section('title', 'Nuevo Producto')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@section('content')
<div class="row justify-content-center">
<div class="col-md-8">
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-plus-circle mr-2 text-primary"></i>Nuevo Producto</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('productos.store') }}" method="POST">
        @csrf
            <div class="row">
                <div class="col-md-8 form-group">
                    <label>Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}" placeholder="Ej: Pan Frances" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 form-group">
                    <label>Categoría <span class="text-danger">*</span></label>
                    <select name="id_categoria" class="form-control @error('id_categoria') is-invalid @enderror" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id }}" {{ old('id_categoria') == $c->id ? 'selected' : '' }}>
                                {{ $c->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_categoria')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2"
                    placeholder="Descripción opcional...">{{ old('descripcion') }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Precio de Venta <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                        <input type="number" name="precio_venta" step="0.01" min="0"
                            class="form-control @error('precio_venta') is-invalid @enderror"
                            value="{{ old('precio_venta', '0.00') }}" required>
                    </div>
                    @error('precio_venta')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 form-group">
                    <label>Costo de Producción</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                        <input type="number" name="costo_produccion" step="0.01" min="0"
                            class="form-control"
                            value="{{ old('costo_produccion', '0.00') }}">
                    </div>
                </div>
                <div class="col-md-3 form-group">
                    <label>Stock Actual <span class="text-danger">*</span></label>
                    <input type="number" name="stock_actual" min="0"
                        class="form-control @error('stock_actual') is-invalid @enderror"
                        value="{{ old('stock_actual', 0) }}" required>
                    @error('stock_actual')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3 form-group">
                    <label>Stock Mínimo <span class="text-danger">*</span></label>
                    <input type="number" name="stock_minimo" min="0"
                        class="form-control @error('stock_minimo') is-invalid @enderror"
                        value="{{ old('stock_minimo', 0) }}" required>
                    @error('stock_minimo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="activo" {{ old('estado','activo') === 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="inactivo" {{ old('estado') === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('productos.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i>Guardar Producto
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
