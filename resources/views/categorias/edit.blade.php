@extends('layouts.app')
@section('title', 'Editar Categoría')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('categorias.index') }}">Categorías</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection
@section('content')
<div class="row justify-content-center"><div class="col-md-6">
<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-edit mr-2 text-warning"></i>Editar: {{ $categoria->nombre }}</h5></div>
    <div class="card-body">
        <form action="{{ route('categorias.update', $categoria) }}" method="POST">@csrf @method('PUT')
            <div class="form-group">
                <label>Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                    value="{{ old('nombre', $categoria->nombre) }}" required>
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2">{{ old('descripcion', $categoria->descripcion) }}</textarea>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="activo"   {{ old('estado',$categoria->estado)==='activo'   ? 'selected':'' }}>Activo</option>
                    <option value="inactivo" {{ old('estado',$categoria->estado)==='inactivo' ? 'selected':'' }}>Inactivo</option>
                </select>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('categorias.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
