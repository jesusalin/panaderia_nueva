<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * "Mi perfil": cada usuario (sea o no admin) cambia su foto, nombre, apodo,
 * correo y su propia contraseña.
 */
class PerfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('perfil.edit', ['usuario' => $request->user()->load('rol')]);
    }

    public function update(Request $request)
    {
        $u = $request->user();
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'apodo'  => 'nullable|string|max:50',
            'usuario'=> ['required', 'string', 'min:3', 'max:100', 'alpha_dash', Rule::unique('usuarios', 'usuario')->ignore($u->id)],
            'email'  => ['required', 'email', 'max:150', Rule::unique('usuarios', 'email')->ignore($u->id)],
            'foto'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'quitar_foto' => 'nullable|boolean',
        ], [
            'foto.image' => 'La foto debe ser una imagen (JPG, PNG o WEBP).',
            'foto.max'   => 'La foto no puede pesar más de 2 MB.',
            'email.unique' => 'Ese correo ya lo usa otra persona.',
            'usuario.unique' => 'Ese nombre de usuario ya lo usa otra persona.',
            'usuario.alpha_dash' => 'El usuario solo puede tener letras, números, guiones y guiones bajos (sin espacios).',
        ]);

        $cambios = collect($datos)->only(['nombre', 'apodo', 'usuario', 'email'])->all();
        $cambioUsuario = $cambios['usuario'] !== $u->usuario;

        if ($request->hasFile('foto')) {
            $this->borrarFoto($u);
            $cambios['foto_perfil'] = $request->file('foto')->store('perfiles', 'public');
        } elseif ($request->boolean('quitar_foto')) {
            $this->borrarFoto($u);
            $cambios['foto_perfil'] = null;
        }

        $u->update($cambios);
        return redirect()->route('perfil.edit')->with('success', $cambioUsuario
            ? "Tus datos se guardaron. Desde ahora entras con el usuario \"{$u->usuario}\"."
            : 'Tus datos se guardaron.');
    }

    public function password(Request $request)
    {
        $u = $request->user();
        $reglas = [
            'password' => 'required|string|min:6|confirmed',
        ];
        // Si entra con una contraseña temporal del admin no se le pide la actual
        if (!$u->debe_cambiar_password) $reglas['password_actual'] = 'required|current_password';

        $request->validate($reglas, [
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.confirmed' => 'Las dos contraseñas nuevas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        if (Hash::check($request->password, $u->password)) {
            return back()->withErrors(['password' => 'Elige una contraseña distinta a la que tienes ahora.']);
        }

        $u->update(['password' => $request->password, 'debe_cambiar_password' => false]);
        return redirect()->route('perfil.edit')->with('success', 'Tu contraseña se cambió.');
    }

    /** Muestra la foto de un usuario (solo a usuarios con sesión; no necesita "storage:link"). */
    public function foto(Usuario $usuario)
    {
        abort_unless($usuario->foto_perfil && Storage::disk('public')->exists($usuario->foto_perfil), 404);
        return response()->file(Storage::disk('public')->path($usuario->foto_perfil), [
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    private function borrarFoto(Usuario $u): void
    {
        if ($u->foto_perfil) Storage::disk('public')->delete($u->foto_perfil);
    }
}
