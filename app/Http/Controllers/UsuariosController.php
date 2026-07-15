<?php
namespace App\Http\Controllers;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\PermisoUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuariosController extends Controller
{
    public function index() {
        $usuarios = Usuario::with(['rol', 'permisos'])->orderBy('nombre')->paginate(15);
        return view('usuarios.index', compact('usuarios'));
    }

    public function create() {
        $roles = Rol::orderBy('nombre')->get();
        $modulos = Usuario::MODULOS;
        return view('usuarios.create', compact('roles', 'modulos'));
    }

    public function store(Request $request) {
        $request->validate([
            'nombre'=>'required|string|max:100',
            'apodo'=>'nullable|string|max:50',
            'usuario'=>'required|unique:usuarios,usuario|max:100',
            'email'=>'required|email|unique:usuarios,email',
            'password'=>'required|min:6|confirmed',
            'id_rol'=>'required|exists:roles,id',
            'estado'=>'required|in:activo,inactivo',
            'permisos'=>'array',
            'permisos.*'=>'in:'.implode(',', array_keys(Usuario::MODULOS)),
        ]);

        $usuario = Usuario::create([
            'nombre'=>$request->nombre,
            'apodo'=>$request->apodo,
            'usuario'=>$request->usuario,
            'email'=>$request->email,
            'password'=>Hash::make($request->password),
            'id_rol'=>$request->id_rol,
            'estado'=>$request->estado,
        ]);

        $this->sincronizarPermisos($usuario, $request->input('permisos', []));

        return redirect()->route('usuarios.index')->with('success','Usuario creado.');
    }

    public function show(Usuario $usuario) {
        return view('usuarios.show', compact('usuario'));
    }

    public function edit(Usuario $usuario) {
        $roles = Rol::orderBy('nombre')->get();
        $modulos = Usuario::MODULOS;
        $permisosActuales = $usuario->modulosAsignados();
        return view('usuarios.edit', compact('usuario', 'roles', 'modulos', 'permisosActuales'));
    }

    public function update(Request $request, Usuario $usuario) {
        $request->validate([
            'nombre'=>'required|string|max:100',
            'apodo'=>'nullable|string|max:50',
            'usuario'=>'required|string|max:100|alpha_dash|unique:usuarios,usuario,'.$usuario->id,
            'email'=>'required|email|unique:usuarios,email,'.$usuario->id,
            'id_rol'=>'required|exists:roles,id',
            'estado'=>'required|in:activo,inactivo',
            'password'=>'nullable|min:6|confirmed',
            'permisos'=>'array',
            'permisos.*'=>'in:'.implode(',', array_keys(Usuario::MODULOS)),
        ], [
            'usuario.alpha_dash' => 'El usuario solo puede tener letras, números, guiones y guiones bajos.',
            'usuario.unique'     => 'Ese nombre de usuario ya está en uso por otra persona.',
        ]);

        $data = $request->only(['nombre','apodo','usuario','email','id_rol','estado']);
        if ($request->filled('password')) $data['password'] = Hash::make($request->password);
        $usuario->update($data);

        $this->sincronizarPermisos($usuario, $request->input('permisos', []));

        return redirect()->route('usuarios.index')->with('success','Usuario actualizado.');
    }

    public function destroy(Usuario $usuario) {
        $usuario->update(['estado'=>'inactivo']);
        return redirect()->route('usuarios.index')->with('success','Usuario desactivado.');
    }

    /**
     * Reemplaza los módulos asignados al usuario por la lista recibida del formulario.
     * Si el usuario es admin, no necesita permisos guardados (ya tiene acceso a todo).
     */
    private function sincronizarPermisos(Usuario $usuario, array $modulos): void
    {
        $modulos = array_values(array_intersect($modulos, array_keys(Usuario::MODULOS)));

        PermisoUsuario::where('id_usuario', $usuario->id)->delete();

        if (!empty($modulos)) {
            $filas = array_map(fn($modulo) => [
                'id_usuario' => $usuario->id,
                'modulo'     => $modulo,
                'created_at' => now(),
            ], $modulos);

            PermisoUsuario::insert($filas);
        }
    }
}
