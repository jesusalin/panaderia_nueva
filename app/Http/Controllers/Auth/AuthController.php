<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
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
            return back()
                ->withErrors(['usuario' => 'Usuario o contraseña incorrectos.'])
                ->withInput($request->only('usuario'));
        }

        if ($usuario->estado === 'inactivo') {
            return back()
                ->withErrors(['usuario' => 'Tu cuenta está desactivada. Contacta al administrador.'])
                ->withInput($request->only('usuario'));
        }

        Auth::login($usuario, $request->boolean('remember'));

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    // ─── REGISTRO ────────────────────────────────────────────

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:100',
            'usuario'  => 'required|string|max:100|unique:usuarios,usuario|alpha_dash',
            'email'    => 'required|email|max:150|unique:usuarios,email',
            'password' => 'required|string|min:6|confirmed',
            'id_rol'   => 'required|exists:roles,id',
        ], [
            'usuario.unique'     => 'Ese nombre de usuario ya está en uso.',
            'usuario.alpha_dash' => 'El usuario solo puede tener letras, números, guiones y guiones bajos.',
            'email.unique'       => 'Ese correo ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min'       => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $usuario = Usuario::create([
            'nombre'   => $request->nombre,
            'usuario'  => $request->usuario,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'id_rol'   => $request->id_rol,
            'estado'   => 'activo',
        ]);

        Auth::login($usuario);

        return redirect()->route('dashboard')
            ->with('success', "¡Bienvenido, {$usuario->nombre}! Tu cuenta fue creada correctamente.");
    }
}
