@extends('layouts.app')
@section('title', 'Editar Usuario')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection
@section('content')
<div class="row justify-content-center"><div class="col-md-7">
<div class="card">
    <div class="card-header bg-white"><h5 class="mb-0"><i class="fas fa-user-edit mr-2 text-warning"></i>Editar: {{ $usuario->nombre }}</h5></div>
    <div class="card-body">
        <form action="{{ route('usuarios.update', $usuario) }}" method="POST">@csrf @method('PUT')
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre', $usuario->nombre) }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Nombre de Usuario</label>
                    <input type="text" class="form-control bg-light" value="{{ $usuario->usuario }}" disabled>
                    <small class="text-muted">El nombre de usuario no se puede cambiar</small>
                </div>
            </div>
            <div class="form-group">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                    value="{{ old('email', $usuario->email) }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Nueva Contraseña <small class="text-muted">(dejar en blanco para no cambiar)</small></label>
                    <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Confirmar Nueva Contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Rol <span class="text-danger">*</span></label>
                    <select name="id_rol" class="form-control" required>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" {{ old('id_rol',$usuario->id_rol) == $r->id ? 'selected' : '' }}>
                                {{ ucfirst($r->nombre) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label>Estado</label>
                    <select name="estado" class="form-control">
                        <option value="activo"   {{ old('estado',$usuario->estado)==='activo'   ? 'selected':'' }}>Activo</option>
                        <option value="inactivo" {{ old('estado',$usuario->estado)==='inactivo' ? 'selected':'' }}>Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <a href="{{ route('usuarios.index') }}" class="btn btn-light mr-2">Cancelar</a>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection
