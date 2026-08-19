<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Cuenta — Muruwasi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap">
    <style>
        * { font-family: 'Nunito', sans-serif; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #b5451b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .register-wrapper {
            width: 100%;
            max-width: 520px;
            padding: 1rem;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #fff;
        }

        .login-brand .brand-icon {
            width: 70px;
            height: 70px;
            background: rgba(255,255,255,.15);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: .8rem;
            border: 2px solid rgba(255,255,255,.3);
        }

        .login-brand h1 { font-size: 1.6rem; font-weight: 800; margin: 0; }
        .login-brand p  { font-size: .85rem; opacity: .7; margin: .2rem 0 0; }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
        }

        .card-body { padding: 2.5rem; }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: .7rem 1rem .7rem 2.8rem;
            font-size: .9rem;
            transition: border-color .2s;
        }

        .form-control:focus {
            border-color: #b5451b;
            box-shadow: 0 0 0 3px rgba(181,69,27,.15);
        }

        .input-icon { position: relative; }
        .input-icon i.icon-left {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            z-index: 10;
        }

        .toggle-pass {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #adb5bd;
            z-index: 10;
        }
        .toggle-pass:hover { color: #495057; }

        .btn-register {
            background: linear-gradient(135deg, #b5451b, #d4632e);
            border: none;
            border-radius: 10px;
            padding: .85rem;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            width: 100%;
            transition: opacity .2s, transform .1s;
        }

        .btn-register:hover { opacity: .9; transform: translateY(-1px); color: #fff; }
        .btn-register:active { transform: translateY(0); }

        .form-label {
            font-weight: 700;
            font-size: .82rem;
            color: #495057;
            margin-bottom: .3rem;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .alert { border-radius: 10px; font-size: .9rem; }

        .login-link {
            text-align: center;
            font-size: .9rem;
            color: #6c757d;
            margin-top: 1.2rem;
        }

        .login-link a { color: #b5451b; font-weight: 700; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }

        .section-title {
            font-size: .75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #b5451b;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: .4rem;
            margin-bottom: 1rem;
        }

        /* Barra de fortaleza de contraseña */
        .strength-bar { height: 4px; border-radius: 2px; margin-top: .4rem; transition: all .3s; }
        .strength-text { font-size: .78rem; margin-top: .2rem; }
    </style>
</head>
<body>
<div class="register-wrapper">

    <div class="login-brand">
        <div class="brand-icon">🍞</div>
        <h1>Muruwasi</h1>
        <p>Sistema de Gestión</p>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="font-weight-bold mb-1">Crear una cuenta</h5>
            <p class="text-muted small mb-4">Completa el formulario para acceder al sistema</p>

            @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <ul class="mb-0 pl-3">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- Datos personales --}}
                <p class="section-title"><i class="fas fa-user mr-1"></i>Datos personales</p>

                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <div class="input-icon">
                        <i class="fas fa-id-card icon-left"></i>
                        <input type="text" name="nombre"
                            class="form-control @error('nombre') is-invalid @enderror"
                            value="{{ old('nombre') }}"
                            placeholder="Ej: Juan Pérez García"
                            autofocus required>
                    </div>
                    @error('nombre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Acceso --}}
                <p class="section-title mt-3"><i class="fas fa-key mr-1"></i>Datos de acceso</p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Nombre de Usuario</label>
                            <div class="input-icon">
                                <i class="fas fa-at icon-left"></i>
                                <input type="text" name="usuario"
                                    class="form-control @error('usuario') is-invalid @enderror"
                                    value="{{ old('usuario') }}"
                                    placeholder="sin espacios"
                                    required>
                            </div>
                            @error('usuario')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Correo Electrónico</label>
                            <div class="input-icon">
                                <i class="fas fa-envelope icon-left"></i>
                                <input type="email" name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}"
                                    placeholder="correo@ejemplo.com"
                                    required>
                            </div>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Contraseña</label>
                            <div class="input-icon" style="position:relative">
                                <i class="fas fa-lock icon-left"></i>
                                <input type="password" name="password" id="passwordField"
                                    class="form-control @error('password') is-invalid @enderror"
                                    placeholder="Mínimo 6 caracteres"
                                    oninput="checkStrength(this.value)"
                                    required>
                                <i class="fas fa-eye toggle-pass" onclick="togglePass('passwordField', this)"></i>
                            </div>
                            <div class="strength-bar bg-light" id="strengthBar"></div>
                            <div class="strength-text text-muted" id="strengthText"></div>
                            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Confirmar Contraseña</label>
                            <div class="input-icon" style="position:relative">
                                <i class="fas fa-lock icon-left"></i>
                                <input type="password" name="password_confirmation" id="passwordConfirm"
                                    class="form-control"
                                    placeholder="Repite tu contraseña"
                                    required>
                                <i class="fas fa-eye toggle-pass" onclick="togglePass('passwordConfirm', this)"></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Rol --}}
                <div class="form-group">
                    <label class="form-label">Tipo de acceso</label>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="custom-control custom-radio">
                                <input type="radio" name="id_rol" value="2" id="rolVendedor"
                                    class="custom-control-input"
                                    {{ old('id_rol', '2') == '2' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="rolVendedor">
                                    <strong>Vendedor</strong><br>
                                    <small class="text-muted">Registrar ventas</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-radio">
                                <input type="radio" name="id_rol" value="3" id="rolAlmacenero"
                                    class="custom-control-input"
                                    {{ old('id_rol') == '3' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="rolAlmacenero">
                                    <strong>Almacenero</strong><br>
                                    <small class="text-muted">Gestión de stock</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-radio">
                                <input type="radio" name="id_rol" value="1" id="rolAdmin"
                                    class="custom-control-input"
                                    {{ old('id_rol') == '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="rolAdmin">
                                    <strong>Admin</strong><br>
                                    <small class="text-muted">Acceso total</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-register mt-2">
                    <i class="fas fa-user-plus mr-2"></i>Crear mi cuenta
                </button>
            </form>

            <div class="login-link">
                ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
            </div>
        </div>
    </div>

    <p class="text-center text-white-50 small mt-3">
        &copy; {{ date('Y') }} Sistema de Gestión de Muruwasi
    </p>
</div>

<script>
function togglePass(id, icon) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function checkStrength(val) {
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { color: '#dc3545', label: 'Muy débil',  width: '20%'  },
        { color: '#fd7e14', label: 'Débil',       width: '40%'  },
        { color: '#ffc107', label: 'Regular',     width: '60%'  },
        { color: '#20c997', label: 'Buena',       width: '80%'  },
        { color: '#28a745', label: 'Muy segura',  width: '100%' },
    ];

    const lvl = levels[Math.min(score, 4)];
    bar.style.background = lvl.color;
    bar.style.width = val.length ? lvl.width : '0';
    text.textContent = val.length ? lvl.label : '';
    text.style.color = lvl.color;
}
</script>
</body>
</html>
