<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Panadería</title>
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
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
            color: #fff;
        }

        .login-brand .brand-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,.15);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin-bottom: 1rem;
            border: 2px solid rgba(255,255,255,.3);
        }

        .login-brand h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
        }

        .login-brand p {
            font-size: .85rem;
            opacity: .7;
            margin: .2rem 0 0;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.4);
            overflow: hidden;
        }

        .card-body {
            padding: 2.5rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: .75rem 1rem .75rem 2.8rem;
            font-size: .95rem;
            transition: border-color .2s;
        }

        .form-control:focus {
            border-color: #b5451b;
            box-shadow: 0 0 0 3px rgba(181,69,27,.15);
        }

        /* ✅ CORRECCIÓN: selector específico para el ícono izquierdo (candado/usuario) */
        .input-icon {
            position: relative;
        }

        .input-icon .icon-left {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            z-index: 10;
            pointer-events: none;
        }

        /* ✅ CORRECCIÓN: el ojo queda a la derecha sin heredar el estilo del candado */
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

        .btn-login {
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

        .btn-login:hover {
            opacity: .9;
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #adb5bd;
            font-size: .85rem;
            margin: 1.2rem 0;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #e9ecef;
        }

        .register-link {
            text-align: center;
            font-size: .9rem;
            color: #6c757d;
        }

        .register-link a {
            color: #b5451b;
            font-weight: 700;
            text-decoration: none;
        }

        .register-link a:hover { text-decoration: underline; }

        .form-label {
            font-weight: 700;
            font-size: .85rem;
            color: #495057;
            margin-bottom: .4rem;
        }

        .alert {
            border-radius: 10px;
            font-size: .9rem;
        }
    </style>
</head>
<body>
<div class="login-wrapper">

    <div class="login-brand">
        <div class="brand-icon">🍞</div>
        <h1>Panadería</h1>
        <p>Sistema de Gestión</p>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="font-weight-bold mb-1">Bienvenido de vuelta</h5>
            <p class="text-muted small mb-4">Ingresa tus credenciales para continuar</p>

            @if($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i>
                {{ $errors->first() }}
            </div>
            @endif

            @if(session('status'))
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-2"></i>
                {{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label">Usuario</label>
                    <div class="input-icon">
                        {{-- ✅ CORRECCIÓN: clase "icon-left" en vez de "i" genérico --}}
                        <i class="fas fa-user icon-left"></i>
                        <input type="text" name="usuario"
                            class="form-control @error('usuario') is-invalid @enderror"
                            value="{{ old('usuario') }}"
                            placeholder="Tu nombre de usuario"
                            autocomplete="username"
                            autofocus required>
                    </div>
                    @error('usuario')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label mb-0">Contraseña</label>
                        @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="small text-muted">¿Olvidaste tu contraseña?</a>
                        @endif
                    </div>
                    <div class="input-icon">
                        {{-- ✅ CORRECCIÓN: clase "icon-left" para el candado --}}
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" name="password" id="passwordField"
                            class="form-control @error('password') is-invalid @enderror"
                            placeholder="Tu contraseña"
                            autocomplete="current-password" required>
                        {{-- ✅ El ojo usa "toggle-pass" que va a la derecha --}}
                        <i class="fas fa-eye toggle-pass" onclick="togglePassword()"></i>
                    </div>
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group d-flex align-items-center">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="remember" name="remember">
                        <label class="custom-control-label small" for="remember">Mantener sesión iniciada</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt mr-2"></i>Ingresar al sistema
                </button>
            </form>

            <div class="divider">o</div>

            <div class="register-link">
                ¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate aquí</a>
            </div>
        </div>
    </div>

    <p class="text-center text-white-50 small mt-3">
        &copy; {{ date('Y') }} Sistema de Gestión de Panadería
    </p>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordField');
    const icon  = document.querySelector('.toggle-pass');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>
