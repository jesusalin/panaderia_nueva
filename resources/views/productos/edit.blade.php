@extends('layouts.app')
@section('title', 'Editar Producto')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('productos.index') }}">Productos</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
<div class="row justify-content-center">
<div class="col-md-8">
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-edit mr-2 text-warning"></i>Editar: {{ $producto->nombre }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('productos.update', $producto) }}" method="POST">
        @csrf @method('PUT')
            <div class="row">
                <div class="col-md-8 form-group">
                    <label>Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre', $producto->nombre) }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 form-group">
                    <label>Categoría <span class="text-danger">*</span></label>
                    <select name="id_categoria" class="form-control" required>
                        @foreach($categorias as $c)
                            <option value="{{ $c->id }}" {{ old('id_categoria', $producto->id_categoria) == $c->id ? 'selected' : '' }}>
                                {{ $c->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2">{{ old('descripcion', $producto->descripcion) }}</textarea>
            </div>

            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Precio de Venta <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                        <input type="number" name="precio_venta" step="0.01" min="0"
                            class="form-control" value="{{ old('precio_venta', $producto->precio_venta) }}" required>
                    </div>
                </div>
                <div class="col-md-3 form-group">
                    <label>Costo de Producción</label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                        <input type="number" name="costo_produccion" step="0.01" min="0"
                            class="form-control" value="{{ old('costo_produccion', $producto->costo_produccion) }}">
                    </div>
                </div>
                <div class="col-md-3 form-group">
                    <label>Stock Actual <span class="text-danger">*</span></label>
                    <input type="number" name="stock_actual" min="0" class="form-control"
                        value="{{ old('stock_actual', $producto->stock_actual) }}" required>
                </div>
                <div class="col-md-3 form-group">
                    <label>Stock Mínimo <span class="text-danger">*</span></label>
                    <input type="number" name="stock_minimo" min="0" class="form-control"
                        value="{{ old('stock_minimo', $producto->stock_minimo) }}" required>
                </div>
            </div>

            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="activo"   {{ old('estado', $producto->estado) === 'activo'   ? 'selected' : '' }}>Activo</option>
                    <option value="inactivo" {{ old('estado', $producto->estado) === 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                </select>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('productos.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save mr-1"></i>Actualizar Producto
                </button>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection
