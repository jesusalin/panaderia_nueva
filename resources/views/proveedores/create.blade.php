@extends('layouts.app')
@section('title', 'Nuevo Proveedor')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection
@section('content')
<div class="row justify-content-center"><div class="col-md-8">
<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-plus-circle mr-2 text-primary"></i>Nuevo Proveedor</h5></div>
    <div class="card-body">
        <form action="{{ route('proveedores.store') }}" method="POST">@csrf
            <div class="row">
                <div class="col-md-8 form-group">
                    <label>Nombre / Razón Social <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}" placeholder="Ej: Distribuidora El Molino S.A.C." required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 form-group">
                    <label>RUC</label>
                    <input type="text" name="ruc" class="form-control" value="{{ old('ruc') }}" placeholder="20123456789">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="{{ old('direccion') }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ old('telefono') }}" placeholder="999 999 999">
                </div>
                <div class="col-md-3 form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>
            </div>
            <div class="form-group">
                <label>Persona de Contacto</label>
                <input type="text" name="contacto" class="form-control" value="{{ old('contacto') }}" placeholder="Nombre del representante">
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('proveedores.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
