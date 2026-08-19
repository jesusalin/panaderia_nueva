@extends('layouts.app')
@section('title', 'Editar Categoría')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('categorias.index') }}">Categorías</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@include('categorias.partials._styles')

@section('content')
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card cat-form-card">
    <div class="cat-form-header">
        <div class="cat-form-icon"><i class="fas fa-edit"></i></div>
        <div>
            <h5>Editar: {{ $categoria->nombre }}</h5>
            <p>{{ $categoria->productos()->count() }} producto(s) en esta categoría</p>
        </div>
    </div>
    <div class="cat-form-body">
        <form action="{{ route('categorias.update', $categoria) }}" method="POST">@csrf @method('PUT')
            <div class="form-group">
                <label>Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                    value="{{ old('nombre', $categoria->nombre) }}" required autofocus>
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Descripción <small class="text-muted">(opcional)</small></label>
                <textarea name="descripcion" class="form-control" rows="3">{{ old('descripcion', $categoria->descripcion) }}</textarea>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="activo"   {{ old('estado',$categoria->estado)==='activo'   ? 'selected':'' }}>Activo</option>
                    <option value="inactivo" {{ old('estado',$categoria->estado)==='inactivo' ? 'selected':'' }}>Inactivo</option>
                </select>
            </div>
            <div class="form-actions">
                <a href="{{ route('categorias.index') }}" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
