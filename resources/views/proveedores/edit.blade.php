@extends('layouts.app')
@section('title', 'Editar Proveedor')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection
@section('content')
<div class="row justify-content-center"><div class="col-md-8">
<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-edit mr-2 text-warning"></i>Editar: {{ $proveedor->nombre }}</h5></div>
    <div class="card-body">
        <form action="{{ route('proveedores.update', $proveedor) }}" method="POST">@csrf @method('PUT')
            <div class="row">
                <div class="col-md-8 form-group">
                    <label>Nombre / Razón Social <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre', $proveedor->nombre) }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 form-group">
                    <label>RUC</label>
                    <input type="text" name="ruc" class="form-control" value="{{ old('ruc', $proveedor->ruc) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="{{ old('direccion', $proveedor->direccion) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono', $proveedor->telefono) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $proveedor->email) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 form-group">
                    <label>Persona de Contacto</label>
                    <input type="text" name="contacto" class="form-control" value="{{ old('contacto', $proveedor->contacto) }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="activo"   {{ old('estado',$proveedor->estado)==='activo'   ? 'selected':'' }}>Activo</option>
                        <option value="inactivo" {{ old('estado',$proveedor->estado)==='inactivo' ? 'selected':'' }}>Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('proveedores.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
