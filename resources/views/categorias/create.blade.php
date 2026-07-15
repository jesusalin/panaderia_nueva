@extends('layouts.app')
@section('title', 'Nueva Categoría')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('categorias.index') }}">Categorías</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@include('categorias.partials._styles')

@section('content')
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card cat-form-card">
    <div class="cat-form-header">
        <div class="cat-form-icon"><i class="fas fa-tags"></i></div>
        <div>
            <h5>Nueva Categoría</h5>
            <p>Agrupa productos similares (ej: Panes, Pasteles, Bebidas)</p>
        </div>
    </div>
    <div class="cat-form-body">
        <form action="{{ route('categorias.store') }}" method="POST">@csrf
            <div class="form-group">
                <label>Nombre <span class="text-danger">*</span></label>
                <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                    value="{{ old('nombre') }}" placeholder="Ej: Panes" required autofocus>
                @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>Descripción <small class="text-muted">(opcional)</small></label>
                <textarea name="descripcion" class="form-control" rows="3" placeholder="Ej: Panes y bollos del día">{{ old('descripcion') }}</textarea>
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado" class="form-control">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="form-actions">
                <a href="{{ route('categorias.index') }}" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
