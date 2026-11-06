{{-- Formulario de usuario (nuevo y editar). Variables: $usuario (null al crear), $roles, $modulos, $existentes, $permisosActuales, $ultimoAdmin --}}
@php
    $editar = isset($usuario) && $usuario;
    $u = $editar ? $usuario : null;
    $yo = $editar && auth()->id() === $u->id;
    $estado = old('estado', $u->estado ?? 'activo');
    $rolSel = (string) old('id_rol', $u->id_rol ?? $roles->firstWhere('nombre', '!=', 'admin')?->id);
    $bloqueoRol = $editar && ($yo || ($ultimoAdmin ?? false)) && $u->isAdmin();
    $textoRol = [
        'admin'   => ['icono' => 'fa-user-shield', 'titulo' => 'Administrador', 'desc' => 'Puede usar todo el sistema y administrar usuarios.'],
        'usuario' => ['icono' => 'fa-user', 'titulo' => 'Usuario', 'desc' => 'Solo las pantallas que marques abajo.'],
    ];
@endphp
@include('partials.form-secciones')

@once
@push('styles')
<style>
    .uf-aviso { display: none; gap: .5rem; align-items: flex-start; border-radius: 10px; padding: .45rem .7rem; font-size: .8rem; margin-top: .4rem; }
    .uf-aviso.ver { display: flex; }
    .uf-aviso i { margin-top: .15rem; }
    .uf-aviso.mal { background: #fdecea; color: #a93226; }
    .uf-aviso.ok { background: #e9f8ef; color: #1e7a4c; }
    body.dark-mode .uf-aviso.mal { background: rgba(231,76,60,.15); color: #f1a097; }
    body.dark-mode .uf-aviso.ok { background: rgba(46,204,113,.12); color: #6ee7a5; }
    .uf-sugerir { font-size: .78rem; margin-top: .3rem; }
    .uf-sugerir button { border: 0; background: #fdf1ea; color: #b5451b; border-radius: 20px; padding: .1rem .6rem; font-weight: 700; margin-left: .2rem; }
    .uf-pass { position: relative; }
    .uf-pass .toggle-pass { position: absolute; right: .9rem; top: 50%; transform: translateY(-50%); cursor: pointer; color: #adb5bd; }
    .uf-fuerza { height: 6px; border-radius: 4px; background: #eee; overflow: hidden; margin-top: .4rem; }
    .uf-fuerza span { display: block; height: 100%; width: 0; transition: width .2s, background .2s; }
    .uf-fuerza-txt { font-size: .74rem; margin-top: .2rem; color: #8a8a9d; }
    .uf-roles { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .6rem; }
    .uf-roles input { position: absolute; opacity: 0; pointer-events: none; }
    .uf-roles label { display: flex; gap: .7rem; align-items: flex-start; margin: 0; border: 1.5px solid #eee5dd; border-radius: 12px; padding: .75rem .9rem; cursor: pointer; background: #fff; }
    .uf-roles label i { width: 34px; height: 34px; border-radius: 10px; background: #f3f0ec; color: #8a8a9d; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .uf-roles label b { display: block; color: #1a1a2e; }
    .uf-roles label small { color: #8a8a9d; font-weight: 400; }
    .uf-roles input:checked + label { border-color: #b5451b; background: #fff8f4; box-shadow: 0 0 0 1px #b5451b inset; }
    .uf-roles input:checked + label i { background: #b5451b; color: #fff; }
    .uf-roles input:disabled + label { cursor: not-allowed; opacity: .6; }
    .uf-roles input:focus-visible + label { outline: 2px solid #b5451b; outline-offset: 2px; }
    body.dark-mode .uf-roles label { background: #24243b; border-color: #33334d; }
    body.dark-mode .uf-roles label b { color: #f0f0f7; }
    body.dark-mode .uf-roles input:checked + label { background: #2c2c44; }
    .uf-vista { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.06); padding: 1rem; text-align: center; }
    body.dark-mode .uf-vista { background: #1f1f33; }
    .uf-vista .uv-av { width: 64px; height: 64px; border-radius: 50%; margin: 0 auto .5rem; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.4rem; color: #fff; background: linear-gradient(135deg, #4a4a5e, #1a1a2e); overflow: hidden; }
    .uf-vista .uv-av.inactivo { background: #adb5bd; }
    .uf-vista .uv-av img { width: 100%; height: 100%; object-fit: cover; }
    .uf-vista b.nm { display: block; color: #1a1a2e; font-size: 1.02rem; }
    body.dark-mode .uf-vista b.nm { color: #f0f0f7; }
    .uf-vista .uv-chips { display: flex; justify-content: center; flex-wrap: wrap; gap: .3rem; margin-top: .5rem; }
    .clave-olvido { display: flex; gap: .8rem; align-items: center; background: #f1f7fd; border: 1px solid #d6e7f7; color: #2c4a66;
        border-radius: 10px; padding: .7rem 1rem; font-size: .82rem; margin-top: .6rem; }
    .clave-olvido > i { font-size: 1.2rem; color: #2980b9; }
    .clave-olvido .btn { white-space: nowrap; margin-left: auto; }
    @media (max-width: 576px) { .clave-olvido { flex-wrap: wrap; } }
    body.dark-mode .clave-olvido { background: #1d2a3a; border-color: #2b4058; color: #c9dcf0; }
    .field-warning { display: flex; gap: .6rem; align-items: flex-start; background: #fff8ec; border: 1px solid #f5deb3; color: #8a6215; border-radius: 10px; padding: .55rem .8rem; font-size: .78rem; margin-top: .4rem; }
    #bloquePermisos .section-label { display: none; }
</style>
@endpush
@endonce

<div class="pfm-grid" id="ufRaiz">
    <div style="min-width:0">
        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-id-card"></i></span><h5>¿Quién es?</h5></div>
            <div class="pfm-card-body">
                <div class="row">
                    <div class="col-md-7 form-group mb-md-0">
                        <label for="inNombre">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="inNombre" maxlength="100" autocomplete="off" class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre', $u->nombre ?? '') }}" placeholder="Ej: María Quispe" required {{ $editar ? '' : 'autofocus' }}>
                        @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5 form-group mb-0">
                        <label for="inApodo">Apodo o puesto <small class="text-muted font-weight-normal">(opcional)</small></label>
                        <input type="text" name="apodo" id="inApodo" maxlength="50" class="form-control @error('apodo') is-invalid @enderror"
                               value="{{ old('apodo', $u->apodo ?? '') }}" placeholder="Ej: Caja 1, Almacén">
                        @error('apodo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-lock"></i></span><h5>¿Con qué entra al sistema?</h5></div>
            <div class="pfm-card-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label for="inUsuario">Usuario <span class="text-danger">*</span></label>
                        <input type="text" name="usuario" id="inUsuario" maxlength="100" autocomplete="off" autocapitalize="none" spellcheck="false"
                               class="form-control @error('usuario') is-invalid @enderror" value="{{ old('usuario', $u->usuario ?? '') }}" placeholder="Ej: mquispe" required>
                        @error('usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="uf-aviso" id="avUsuario"><i class="fas"></i><span></span></div>
                        <div class="uf-sugerir" id="ufSugerir" hidden>Sugerencia: <button type="button" id="btnSugerido"></button></div>
                        @if($editar)
                            <div class="field-warning"><i class="fas fa-triangle-exclamation"></i><span>Es lo que escribe para entrar. Si lo cambias, avísale: no pierde su historial.</span></div>
                        @endif
                    </div>
                    <div class="col-md-6 form-group">
                        <label for="inEmail">Correo <span class="text-danger">*</span></label>
                        <input type="email" name="email" id="inEmail" maxlength="150" autocomplete="off" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $u->email ?? '') }}" placeholder="maria@muruwasi.pe" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="uf-aviso" id="avEmail"><i class="fas"></i><span></span></div>
                    </div>
                    <div class="col-md-6 form-group mb-md-0">
                        <label for="passField1">{{ $editar ? 'Nueva contraseña' : 'Contraseña' }} @if($editar)<small class="text-muted font-weight-normal">(vacío = no cambia)</small>@else<span class="text-danger">*</span>@endif</label>
                        <div class="uf-pass">
                            <input type="password" name="password" id="passField1" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror" {{ $editar ? '' : 'required' }}>
                            <i class="fas fa-eye toggle-pass" data-ver="passField1" role="button" aria-label="Mostrar contraseña"></i>
                        </div>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="uf-fuerza"><span id="ufFuerza"></span></div>
                        <div class="uf-fuerza-txt" id="ufFuerzaTxt">Mínimo 6 caracteres.</div>
                    </div>
                    <div class="col-md-6 form-group mb-0">
                        <label for="passField2">Repetir contraseña</label>
                        <div class="uf-pass">
                            <input type="password" name="password_confirmation" id="passField2" autocomplete="new-password" class="form-control" {{ $editar ? '' : 'required' }}>
                            <i class="fas fa-eye toggle-pass" data-ver="passField2" role="button" aria-label="Mostrar contraseña"></i>
                        </div>
                        <div class="uf-aviso" id="avPass"><i class="fas"></i><span></span></div>
                        <button type="button" class="btn btn-sm btn-light mt-2" id="btnGenerarPass"><i class="fas fa-magic mr-1"></i>Generar una contraseña</button>
                    </div>
                </div>
                @if($editar && !$yo)
                    <div class="clave-olvido">
                        <i class="fas fa-question-circle"></i>
                        <div><strong>¿Olvidó su contraseña?</strong> Nadie puede verla, ni el admin. Escribe una nueva arriba o genera una aquí: se mostrará una sola vez para que se la des.</div>
                        <button type="submit" form="formClaveTemporal" class="btn btn-sm btn-outline-primary"><i class="fas fa-key mr-1"></i>Darle una nueva</button>
                    </div>
                @endif
            </div>
        </div>

        <div class="pfm-card">
            <div class="pfm-card-head"><span class="hi"><i class="fas fa-user-shield"></i></span><h5>¿Qué puede hacer?</h5></div>
            <div class="pfm-card-body">
                <div class="uf-roles" role="radiogroup" aria-label="Rol">
                    @foreach($roles as $r)
                        @php $tr = $textoRol[$r->nombre] ?? ['icono' => 'fa-user-tag', 'titulo' => ucfirst($r->nombre), 'desc' => '']; @endphp
                        <input type="radio" name="id_rol" id="rol-{{ $r->id }}" value="{{ $r->id }}" data-nombre="{{ $r->nombre }}" {{ $rolSel === (string) $r->id ? 'checked' : '' }}
                               {{ $bloqueoRol && $r->nombre !== 'admin' ? 'disabled' : '' }} required>
                        <label for="rol-{{ $r->id }}"><i class="fas {{ $tr['icono'] }}"></i><span><b>{{ $tr['titulo'] }}</b><small>{{ $tr['desc'] }}</small></span></label>
                    @endforeach
                </div>
                @error('id_rol')<div class="text-danger small mt-2"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</div>@enderror
                @if($bloqueoRol)
                    <p class="small text-muted mt-2 mb-0"><i class="fas fa-lock mr-1"></i>{{ $yo ? 'No puedes quitarte el rol de administrador a ti mismo.' : 'Es el único administrador activo: no se le puede quitar el rol.' }}</p>
                @endif
                <div class="mt-3">
                    @include('usuarios.partials._permisos', ['modulos' => $modulos, 'seleccionados' => $permisosActuales ?? []])
                </div>
            </div>
        </div>
    </div>

    <div class="pfm-lado">
        <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así quedará la cuenta</span>
        <div class="uf-vista mb-3">
            <div class="uv-av" id="uvAv">@if($editar && $u->foto_url)<img src="{{ $u->foto_url }}" alt="">@else{{ $editar ? $u->iniciales : '?' }}@endif</div>
            <b class="nm" id="uvNombre">{{ $u->nombre ?? 'Nombre' }}</b>
            <small class="text-muted" id="uvUsuario">usuario</small>
            <div class="uv-chips"><span class="badge-soft badge-soft-secondary" id="uvRol">Rol</span><span class="badge-soft badge-soft-info" id="uvPermisos"></span></div>
        </div>
        <div class="pfm-card">
            <div class="pfm-card-body">
                @if($yo || ($ultimoAdmin ?? false))
                    <input type="hidden" name="estado" value="activo">
                    <p class="small text-muted mb-2"><i class="fas fa-lock mr-1"></i>{{ $yo ? 'Tu propia cuenta siempre queda activa.' : 'Único administrador activo: queda activo.' }}</p>
                @else
                    <div class="pfm-switch mb-2">
                        <div class="sw-txt"><b>Activo</b><span>Si lo apagas, no puede ingresar; su historial se conserva.</span></div>
                        <input type="hidden" name="estado" id="inEstado" value="{{ $estado }}">
                        <label class="pfm-toggle"><input type="checkbox" id="swEstado" {{ $estado === 'activo' ? 'checked' : '' }} aria-label="Cuenta activa"><span></span></label>
                    </div>
                @endif
                <ul class="pfm-check mb-2">
                    <li data-chk="usuario"><i class="fas fa-circle"></i><span>Usuario libre y sin espacios</span></li>
                    <li data-chk="email"><i class="fas fa-circle"></i><span>Correo sin repetir</span></li>
                    <li data-chk="pass"><i class="fas fa-circle"></i><span>{{ $editar ? 'Contraseña (solo si la cambias)' : 'Contraseña de 6+ caracteres' }}</span></li>
                    <li data-chk="permisos"><i class="fas fa-circle"></i><span>Qué pantallas puede usar</span></li>
                </ul>
                <button type="submit" id="btnGuardarUsr" class="btn {{ $editar ? 'btn-warning' : 'btn-primary' }} pfm-guardar">
                    <i class="fas fa-save mr-1"></i>{{ $editar ? 'Guardar cambios' : 'Crear usuario' }}
                </button>
                <a href="{{ $volver ?? \App\Support\Volver::url(route('usuarios.index')) }}" class="btn btn-link btn-block btn-sm text-muted">Cancelar</a>
            </div>
        </div>
    </div>
</div>

<script type="application/json" id="ufExistentes">@json($existentes ?? ['usuarios' => [], 'emails' => []])</script>

@push('scripts')
<script>
(function () {
    const $ = id => document.getElementById(id);
    const esc = t => String(t).replace(/[&<>"]/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ch]));
    const ex = JSON.parse($('ufExistentes').textContent || '{}');
    const usados = ex.usuarios || {}, correos = ex.emails || {};
    const editar = @json($editar);
    const tieneFoto = @json($editar && $u->foto_url);
    let usuarioTocado = editar || !!$('inUsuario').value;

    const sinTildes = s => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    function sugerencia(nombre) {
        const p = sinTildes(nombre).replace(/[^a-z\s]/g, '').trim().split(/\s+/).filter(Boolean);
        if (!p.length) return '';
        let base = p.length > 1 ? p[0][0] + p[1] : p[0];
        let s = base, i = 2;
        while (usados[s]) s = base + i++;
        return s;
    }
    function iniciales(n) {
        const p = n.trim().split(/\s+/).filter(Boolean);
        return ((p[0] || '?')[0] + ((p[1] || '')[0] || '')).toUpperCase();
    }
    function aviso(id, clase, icono, html) {
        const a = $(id); a.className = 'uf-aviso ' + (clase ? clase + ' ver' : '');
        a.querySelector('i').className = 'fas ' + icono; a.querySelector('span').innerHTML = html || '';
    }
    function fuerza(p) {
        let f = 0;
        if (p.length >= 6) f++; if (p.length >= 10) f++;
        if (/\d/.test(p) && /[a-zA-Z]/.test(p)) f++; if (/[^a-zA-Z0-9]/.test(p) || /[A-Z]/.test(p)) f++;
        return f;
    }
    function rolActual() { const r = document.querySelector('#ufRaiz input[name=id_rol]:checked'); return r ? r.dataset.nombre : ''; }

    function pintar() {
        const nombre = $('inNombre').value.trim();
        // usuario sugerido desde el nombre (al crear, mientras no lo escriba a mano)
        const sug = sugerencia(nombre);
        if (!usuarioTocado && sug) $('inUsuario').value = sug;
        $('ufSugerir').hidden = !sug || $('inUsuario').value === sug || editar;
        $('btnSugerido').textContent = sug;

        const us = $('inUsuario').value.trim().toLowerCase();
        let usOk = false;
        if (!us) aviso('avUsuario', '', '', '');
        else if (!/^[a-z0-9_-]+$/.test(us)) aviso('avUsuario', 'mal', 'fa-times-circle', 'Sin espacios ni tildes: solo letras, números, - y _.');
        else if (usados[us]) aviso('avUsuario', 'mal', 'fa-times-circle', 'Ya lo usa <b>' + esc(usados[us]) + '</b>.');
        else { usOk = true; aviso('avUsuario', 'ok', 'fa-check-circle', 'Disponible.'); }

        const em = $('inEmail').value.trim().toLowerCase();
        const emOk = !!em && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em) && !correos[em];
        if (em && correos[em]) aviso('avEmail', 'mal', 'fa-times-circle', 'Ya lo usa <b>' + esc(correos[em]) + '</b>.'); else aviso('avEmail', '', '', '');

        const p1 = $('passField1').value, p2 = $('passField2').value, f = fuerza(p1);
        const colores = ['#e74c3c', '#e74c3c', '#f39c12', '#2ecc71', '#1e8e5a'], textos = ['Muy corta', 'Débil', 'Aceptable', 'Buena', 'Muy buena'];
        $('ufFuerza').style.width = p1 ? Math.max(15, f * 25) + '%' : '0';
        $('ufFuerza').style.background = colores[f];
        $('ufFuerzaTxt').textContent = p1 ? (p1.length < 6 ? 'Le faltan ' + (6 - p1.length) + ' caracteres.' : textos[f]) : (editar ? 'Déjala vacía para no cambiarla.' : 'Mínimo 6 caracteres.');
        let passOk = editar && !p1 && !p2;
        if (p1 || p2) {
            if (p1.length < 6) aviso('avPass', '', '', '');
            else if (p2 && p1 !== p2) aviso('avPass', 'mal', 'fa-times-circle', 'No coinciden.');
            else if (p2) { passOk = true; aviso('avPass', 'ok', 'fa-check-circle', 'Coinciden.'); }
            else aviso('avPass', '', '', '');
        } else aviso('avPass', '', '', '');

        const esAdmin = rolActual() === 'admin';
        const nPerm = document.querySelectorAll('#bloquePermisos .js-perm-pantalla:checked').length;
        const activo = !$('swEstado') || $('swEstado').checked;
        if (!tieneFoto) $('uvAv').textContent = nombre ? iniciales(nombre) : '?';
        $('uvAv').classList.toggle('inactivo', !activo);
        $('uvNombre').textContent = nombre || 'Nombre';
        $('uvUsuario').textContent = (us ? '@' + us : 'usuario') + (activo ? '' : ' · inactivo');
        $('uvRol').textContent = esAdmin ? 'Administrador' : 'Usuario';
        $('uvRol').className = 'badge-soft ' + (esAdmin ? 'badge-soft-danger' : 'badge-soft-secondary');
        $('uvPermisos').textContent = esAdmin ? 'Todo el sistema' : (nPerm ? nPerm + ' pantalla(s)' : 'Solo el Dashboard');

        const marcar = (k, ok, mal) => {
            const li = document.querySelector('#ufRaiz [data-chk=' + k + ']'); li.className = ok ? 'ok' : 'no';
            li.querySelector('i').className = 'fas ' + (ok ? 'fa-check-circle' : (mal ? 'fa-times-circle text-danger' : 'fa-circle'));
        };
        marcar('usuario', usOk, !!us && !usOk);
        marcar('email', emOk, !!em && !emOk);
        marcar('pass', passOk, (p1 || p2) && !passOk);
        marcar('permisos', esAdmin || nPerm > 0, false);
        $('btnGuardarUsr').disabled = !nombre || !usOk || !emOk || !passOk || !rolActual();
    }

    $('inUsuario').addEventListener('input', () => { usuarioTocado = $('inUsuario').value.trim() !== ''; });
    $('btnSugerido').addEventListener('click', () => { $('inUsuario').value = $('btnSugerido').textContent; usuarioTocado = true; pintar(); });
    document.querySelectorAll('#ufRaiz [data-ver]').forEach(ic => ic.addEventListener('click', () => {
        const i = $(ic.dataset.ver), ver = i.type === 'password';
        i.type = ver ? 'text' : 'password'; ic.classList.toggle('fa-eye', !ver); ic.classList.toggle('fa-eye-slash', ver);
    }));
    $('btnGenerarPass').addEventListener('click', () => {
        const l = 'abcdefghjkmnpqrstuvwxyz', r = () => l[Math.floor(Math.random() * l.length)];
        const clave = r() + r() + r() + r() + '-' + (2000 + Math.floor(Math.random() * 8000));
        ['passField1', 'passField2'].forEach(id => { $(id).value = clave; $(id).type = 'text'; });
        document.querySelectorAll('#ufRaiz [data-ver]').forEach(ic => { ic.classList.remove('fa-eye'); ic.classList.add('fa-eye-slash'); });
        if (window.toastr) toastr.info('Anótala y dásela: ' + clave);
        pintar();
    });
    function togglePermisos() {
        const esAdmin = rolActual() === 'admin';
        document.querySelector('#bloquePermisos .permisos-grid').style.display = esAdmin ? 'none' : 'grid';
        $('avisoAdminTotal').style.display = esAdmin ? 'flex' : 'none';
    }
    document.querySelectorAll('#ufRaiz input[name=id_rol]').forEach(r => r.addEventListener('change', () => { togglePermisos(); pintar(); }));
    if ($('swEstado')) $('swEstado').addEventListener('change', function () { $('inEstado').value = this.checked ? 'activo' : 'inactivo'; pintar(); });
    $('ufRaiz').addEventListener('input', pintar);
    $('ufRaiz').addEventListener('change', pintar);
    $('ufRaiz').addEventListener('click', e => { if (e.target.closest('[data-plantilla]')) setTimeout(pintar, 0); });
    togglePermisos(); pintar();
})();
</script>
@endpush
