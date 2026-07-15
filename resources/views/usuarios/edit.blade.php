@extends('layouts.app')
@section('title', 'Editar Usuario')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@include('usuarios.partials._styles')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
<div class="card usuario-form-card">

    <div class="usuario-form-header">
        <div class="usuario-avatar">{{ strtoupper(substr($usuario->nombre,0,1)) }}</div>
        <div>
            <h5>Editar: {{ $usuario->nombre }}</h5>
            <p><span>@</span>{{ $usuario->usuario }} &middot; {{ ucfirst($usuario->rol->nombre) }}</p>
        </div>
    </div>

    <div class="usuario-form-body">
        <form action="{{ route('usuarios.update', $usuario) }}" method="POST">@csrf @method('PUT')

            <label class="section-label"><i class="fas fa-id-card mr-1"></i> Información personal</label>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre', $usuario->nombre) }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Apodo / Etiqueta <small class="text-muted">(opcional)</small></label>
                    <input type="text" name="apodo" class="form-control @error('apodo') is-invalid @enderror"
                        value="{{ old('apodo', $usuario->apodo) }}" placeholder="Ej: Almacén, Caja 1, Ventas mostrador">
                    @error('apodo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <label class="section-label"><i class="fas fa-lock mr-1"></i> Credenciales de acceso</label>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Nombre de Usuario <span class="text-danger">*</span></label>
                    <input type="text" name="usuario" class="form-control @error('usuario') is-invalid @enderror"
                        value="{{ old('usuario', $usuario->usuario) }}" required>
                    @error('usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="field-warning">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span>Esto es lo que la persona escribe para entrar al sistema. Si lo cambias, avísale — no pierde sus ventas, compras ni historial, solo cambia con qué nombre inicia sesión. Si te equivocas, puedes volver a editarlo en cualquier momento.</span>
                    </div>
                </div>
                <div class="col-md-6 form-group">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $usuario->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Nueva Contraseña <small class="text-muted">(dejar en blanco para no cambiar)</small></label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="passField1" class="form-control @error('password') is-invalid @enderror">
                        <i class="fas fa-eye toggle-pass" onclick="togglePass('passField1', this)"></i>
                    </div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Confirmar Nueva Contraseña</label>
                    <div class="password-wrap">
                        <input type="password" name="password_confirmation" id="passField2" class="form-control">
                        <i class="fas fa-eye toggle-pass" onclick="togglePass('passField2', this)"></i>
                    </div>
                </div>
            </div>

            <label class="section-label"><i class="fas fa-user-shield mr-1"></i> Rol y estado</label>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Rol <span class="text-danger">*</span></label>
                    <select name="id_rol" id="id_rol" class="form-control" required>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" data-nombre="{{ $r->nombre }}" {{ old('id_rol',$usuario->id_rol) == $r->id ? 'selected' : '' }}>
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

            @include('usuarios.partials._permisos', ['modulos' => $modulos, 'seleccionados' => $permisosActuales])

            <div class="form-actions">
                <a href="{{ route('usuarios.index') }}" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Actualizar</button>
            </div>
        </form>
    </div>
</div>
</div></div>
@endsection

@push('scripts')
<script>
function togglePass(id, icon) {
    const input = document.getElementById(id);
    if (input.type === 'password') { input.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
    else { input.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}

function togglePermisos() {
    const select = document.getElementById('id_rol');
    const opt = select.options[select.selectedIndex];
    const esAdmin = opt && opt.dataset.nombre === 'admin';
    document.getElementById('bloquePermisos').querySelector('.permisos-grid').style.display = esAdmin ? 'none' : 'grid';
    document.getElementById('avisoAdminTotal').style.display = esAdmin ? 'flex' : 'none';
}
document.getElementById('id_rol').addEventListener('change', togglePermisos);
document.addEventListener('DOMContentLoaded', togglePermisos);
</script>
@endpush
