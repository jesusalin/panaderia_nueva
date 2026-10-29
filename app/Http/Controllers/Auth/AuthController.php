<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\ActividadUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ─── LOGIN ───────────────────────────────────────────────

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'usuario'  => 'required|string',
            'password' => 'required|string',
        ], [
            'usuario.required'  => 'El nombre de usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        $usuario = Usuario::where('usuario', $request->usuario)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            ActividadUsuario::registrar('login_fallido',
                $usuario ? 'Contraseña incorrecta al intentar ingresar' : 'Intento de ingreso con un usuario que no existe ("' . mb_substr($request->usuario, 0, 50) . '")',
                'Sesión', $usuario, $usuario ? null : mb_substr($request->usuario, 0, 100));
            return back()
                ->withErrors(['usuario' => 'Usuario o contraseña incorrectos.'])
                ->withInput($request->only('usuario'));
        }

        if ($usuario->estado === 'inactivo') {
            ActividadUsuario::registrar('login_fallido', 'Intentó ingresar con la cuenta desactivada', 'Sesión', $usuario);
            return back()
                ->withErrors(['usuario' => 'Tu cuenta está desactivada. Contacta al administrador.'])
                ->withInput($request->only('usuario'));
        }

        Auth::login($usuario, $request->boolean('remember'));
        ActividadUsuario::registrar('inicio_sesion', 'Inició sesión', 'Sesión', $usuario);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            ActividadUsuario::registrar('cierre_sesion', 'Cerró sesión', 'Sesión');
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
