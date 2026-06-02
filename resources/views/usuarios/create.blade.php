@extends('layouts.app')
@section('title', 'Nuevo Usuario')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection
@section('content')
<div class="row justify-content-center"><div class="col-md-7">
<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-user-plus mr-2 text-primary"></i>Nuevo Usuario</h5></div>
    <div class="card-body">
        <form action="{{ route('usuarios.store') }}" method="POST">@csrf
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Nombre de Usuario <span class="text-danger">*</span></label>
                    <input type="text" name="usuario" class="form-control @error('usuario') is-invalid @enderror"
                        value="{{ old('usuario') }}" required>
                    @error('usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Confirmar Contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Rol <span class="text-danger">*</span></label>
                    <select name="id_rol" class="form-control @error('id_rol') is-invalid @enderror" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ old('id_rol') == $r->id ? 'selected' : '' }}>
                                {{ ucfirst($r->nombre) }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_rol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('usuarios.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
