@extends('layouts.app')
@section('title', 'Nuevo Usuario')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active">Nuevo</li>
@endsection

@include('usuarios.partials._styles')

@section('content')
<div class="row justify-content-center"><div class="col-lg-9">
<div class="card usuario-form-card">

    <div class="usuario-form-header">
        <div class="usuario-avatar" id="avatarPreview">?</div>
        <div>
            <h5>Nuevo Usuario</h5>
            <p>Crea una cuenta y define a qué módulos puede entrar</p>
        </div>
    </div>

    <div class="usuario-form-body">
        <form action="{{ route('usuarios.store') }}" method="POST">@csrf

            <label class="section-label"><i class="fas fa-id-card mr-1"></i> Información personal</label>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" id="inputNombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre') }}" required>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Apodo / Etiqueta <small class="text-muted">(opcional)</small></label>
                    <input type="text" name="apodo" class="form-control @error('apodo') is-invalid @enderror"
                        value="{{ old('apodo') }}" placeholder="Ej: Almacén, Caja 1, Ventas mostrador">
                    @error('apodo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Ayuda a identificar rápido qué hace este usuario en el menú de usuarios.</small>
                </div>
            </div>

            <label class="section-label"><i class="fas fa-lock mr-1"></i> Credenciales de acceso</label>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Nombre de Usuario <span class="text-danger">*</span></label>
                    <input type="text" name="usuario" class="form-control @error('usuario') is-invalid @enderror"
                        value="{{ old('usuario') }}" placeholder="Ej: jperez" required>
                    @error('usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Contraseña <span class="text-danger">*</span></label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="passField1" class="form-control @error('password') is-invalid @enderror" required>
                        <i class="fas fa-eye toggle-pass" onclick="togglePass('passField1', this)"></i>
                    </div>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Confirmar Contraseña <span class="text-danger">*</span></label>
                    <div class="password-wrap">
                        <input type="password" name="password_confirmation" id="passField2" class="form-control" required>
                        <i class="fas fa-eye toggle-pass" onclick="togglePass('passField2', this)"></i>
                    </div>
                </div>
            </div>

            <label class="section-label"><i class="fas fa-user-shield mr-1"></i> Rol y estado</label>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Rol <span class="text-danger">*</span></label>
                    <select name="id_rol" id="id_rol" class="form-control @error('id_rol') is-invalid @enderror" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}" data-nombre="{{ $r->nombre }}" {{ old('id_rol') == $r->id ? 'selected' : '' }}>
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

            @include('usuarios.partials._permisos', ['modulos' => $modulos, 'seleccionados' => []])

            <div class="form-actions">
                <a href="{{ route('usuarios.index') }}" class="btn btn-light">Cancelar</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar Usuario</button>
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

// Actualiza el avatar con la inicial del nombre mientras se escribe
const avatar = document.getElementById('avatarPreview');
document.getElementById('inputNombre').addEventListener('input', function () {
    avatar.textContent = this.value.trim() ? this.value.trim().charAt(0).toUpperCase() : '?';
});
</script>
@endpush
