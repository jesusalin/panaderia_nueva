@extends('layouts.app')
@section('title', 'Editar Materia Prima')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection
@section('content')
<div class="row justify-content-center"><div class="col-md-7">
<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-edit mr-2 text-warning"></i>Editar: {{ $materiaPrima->nombre }}</h5></div>
    <div class="card-body">
        <form action="{{ route('materia-prima.update', $materiaPrima) }}" method="POST">@csrf @method('PUT')
            <div class="row">
                <div class="col-md-8 form-group">
                    <label>Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre', $materiaPrima->nombre) }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 form-group">
                    <label>Unidad de Medida <span class="text-danger">*</span></label>
                    <select name="id_unidad" class="form-control" required>
                        @foreach($unidades as $u)
                            <option value="{{ $u->id }}" {{ old('id_unidad',$materiaPrima->id_unidad) == $u->id ? 'selected' : '' }}>
                                {{ $u->nombre }} ({{ $u->abreviatura }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Stock Actual <span class="text-danger">*</span></label>
                    <input type="number" name="stock_actual" step="0.001" min="0" class="form-control"
                        value="{{ old('stock_actual', $materiaPrima->stock_actual) }}" required>
                </div>
                <div class="col-md-4 form-group">
                    <label>Stock Mínimo <span class="text-danger">*</span></label>
                    <input type="number" name="stock_minimo" step="0.001" min="0" class="form-control"
                        value="{{ old('stock_minimo', $materiaPrima->stock_minimo) }}" required>
                </div>
                <div class="col-md-4 form-group">
                    <label>Costo Unitario <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">S/</span></div>
                        <input type="number" name="costo_unitario" step="0.01" min="0" class="form-control"
                            value="{{ old('costo_unitario', $materiaPrima->costo_unitario) }}" required>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="activo"   {{ old('estado',$materiaPrima->estado)==='activo'   ? 'selected':'' }}>Activo</option>
                    <option value="inactivo" {{ old('estado',$materiaPrima->estado)==='inactivo' ? 'selected':'' }}>Inactivo</option>
                </select>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('materia-prima.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
