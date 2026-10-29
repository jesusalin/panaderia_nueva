@extends('layouts.app')
@section('title', 'Mi perfil')
@section('breadcrumb') <li class="breadcrumb-item active">Mi perfil</li> @endsection

@include('usuarios.partials._styles')

@push('styles')
<style>
    .perfil-foto { display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap; }
    .perfil-foto .avatar-usuario { box-shadow: 0 0 0 4px #fff, 0 0 0 5px #e9ecef; }
    .perfil-foto .pf-botones { display: flex; flex-direction: column; gap: .4rem; align-items: flex-start; }
    .perfil-foto .pf-ayuda { font-size: .78rem; color: #8a8a9d; }
    .perfil-dato { font-size: .85rem; color: #6c757d; }
    .perfil-dato b { color: #1a1a2e; }
    body.dark-mode .perfil-dato b { color: #f0f0f7; }
    .perfil-temporal { border: 2px solid #f39c12; }
</style>
@endpush

@section('content')
@php $temporal = $usuario->debe_cambiar_password; @endphp
<div class="row justify-content-center"><div class="col-lg-8 col-xl-7">

<div class="card usuario-form-card">
    <div class="usuario-form-header">
        @include('usuarios.partials._avatar', ['u' => $usuario, 'tam' => 56, 'clase' => 'usuario-avatar'])
        <div>
            <h5>{{ $usuario->nombre }}</h5>
            <p>{{ $usuario->apodo ? $usuario->apodo . ' · ' : '' }}{{ ucfirst($usuario->rol->nombre ?? '') }}</p>
        </div>
    </div>

    <div class="usuario-form-body">
        @if(!$temporal)
        <form action="{{ route('perfil.update') }}" method="POST" enctype="multipart/form-data">
            @csrf @method('PUT')

            <label class="section-label"><i class="fas fa-camera mr-1"></i> Foto de perfil</label>
            <div class="perfil-foto">
                <span id="pfVista">@include('usuarios.partials._avatar', ['u' => $usuario, 'tam' => 96])</span>
                <div class="pf-botones">
                    <label class="btn btn-outline-primary btn-sm mb-0">
                        <i class="fas fa-upload mr-1"></i>{{ $usuario->foto_url ? 'Cambiar foto' : 'Subir foto' }}
                        <input type="file" name="foto" id="pfArchivo" accept="image/jpeg,image/png,image/webp" hidden>
                    </label>
                    @if($usuario->foto_url)
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="pfQuitar" name="quitar_foto" value="1">
                            <label class="custom-control-label" for="pfQuitar">Quitar foto</label>
                        </div>
                    @endif
                    <span class="pf-ayuda">JPG, PNG o WEBP, hasta 2 MB.</span>
                </div>
            </div>

            <label class="section-label"><i class="fas fa-id-card mr-1"></i> Mis datos</label>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Nombre completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                        value="{{ old('nombre', $usuario->nombre) }}" required maxlength="100">
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Apodo <small class="text-muted">(cómo te conocen, opcional)</small></label>
                    <input type="text" name="apodo" class="form-control @error('apodo') is-invalid @enderror"
                        value="{{ old('apodo', $usuario->apodo) }}" maxlength="50" placeholder="Ej: Caja 1, Almacén">
                    @error('apodo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Correo (Gmail u otro) <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email', $usuario->email) }}" required maxlength="150" placeholder="tucorreo@gmail.com">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 form-group">
                    <label>Usuario para entrar <span class="text-danger">*</span></label>
                    <input type="text" name="usuario" class="form-control @error('usuario') is-invalid @enderror"
                        value="{{ old('usuario', $usuario->usuario) }}" required minlength="3" maxlength="100" pattern="[A-Za-z0-9_\-]+"
                        title="Letras, números, guiones o guiones bajos, sin espacios" autocomplete="username">
                    @error('usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Es lo que escribes al iniciar sesión. Si lo cambias, desde ahora entras con el nuevo.</small>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Guardar mis datos</button>
            </div>
        </form>
        @endif

        <form action="{{ route('perfil.password') }}" method="POST" class="{{ $temporal ? '' : 'mt-2' }}">
            @csrf @method('PUT')
            <label class="section-label"><i class="fas fa-lock mr-1"></i> {{ $temporal ? 'Elige tu propia contraseña' : 'Cambiar mi contraseña' }}</label>
            @if($temporal)
                <p class="perfil-dato">El administrador te dio una contraseña temporal. Escribe una nueva que solo tú sepas para seguir usando el sistema.</p>
            @endif
            <div class="row">
                @if(!$temporal)
                <div class="col-md-4 form-group">
                    <label>Contraseña actual</label>
                    <div class="password-wrap">
                        <input type="password" name="password_actual" id="pfActual" class="form-control @error('password_actual') is-invalid @enderror" required autocomplete="current-password">
                        <i class="fas fa-eye toggle-pass" onclick="togglePass('pfActual', this)"></i>
                    </div>
                    @error('password_actual')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                @endif
                <div class="col-md-{{ $temporal ? 6 : 4 }} form-group">
                    <label>Contraseña nueva</label>
                    <div class="password-wrap">
                        <input type="password" name="password" id="pfNueva" class="form-control @error('password') is-invalid @enderror" required minlength="6" autocomplete="new-password" {{ $temporal ? 'autofocus' : '' }}>
                        <i class="fas fa-eye toggle-pass" onclick="togglePass('pfNueva', this)"></i>
                    </div>
                    @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-{{ $temporal ? 6 : 4 }} form-group">
                    <label>Repetir la nueva</label>
                    <div class="password-wrap">
                        <input type="password" name="password_confirmation" id="pfNueva2" class="form-control" required minlength="6" autocomplete="new-password">
                        <i class="fas fa-eye toggle-pass" onclick="togglePass('pfNueva2', this)"></i>
                    </div>
                </div>
            </div>
            <div class="form-actions" style="margin-top:.5rem;">
                <button type="submit" class="btn btn-warning"><i class="fas fa-key mr-1"></i>{{ $temporal ? 'Guardar y continuar' : 'Cambiar contraseña' }}</button>
            </div>
        </form>
    </div>
</div>

</div></div>
@endsection

@push('scripts')
<script>
function togglePass(id, icon) {
    var input = document.getElementById(id);
    if (input.type === 'password') { input.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
    else { input.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
}
(function () {
    var archivo = document.getElementById('pfArchivo');
    if (!archivo) return;
    archivo.addEventListener('change', function () {
        var f = archivo.files[0];
        if (!f) return;
        var ayuda = document.querySelector('.pf-ayuda');
        if (f.size > 2 * 1024 * 1024) {
            ayuda.textContent = 'Esa foto pesa más de 2 MB. Elige una más liviana.'; ayuda.style.color = '#c0392b';
            archivo.value = ''; return;
        }
        ayuda.textContent = 'Foto lista: pulsa "Guardar mis datos".'; ayuda.style.color = '#1e8e5a';
        var url = URL.createObjectURL(f);
        document.querySelector('#pfVista .avatar-usuario').innerHTML = '<img src="' + url + '" alt="Vista previa">';
        var quitar = document.getElementById('pfQuitar'); if (quitar) quitar.checked = false;
    });
})();
</script>
@endpush
