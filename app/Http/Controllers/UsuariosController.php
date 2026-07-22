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
        $query = Usuario::with(['rol', 'permisos'])
            ->withCount(['ventas', 'compras', 'producciones', 'movimientos', 'kardex', 'tiempos'])
            ->withMax('ventas', 'fecha_venta');

        if (request('buscar')) {
            $buscar = request('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('usuario', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }
        if (request('rol')) {
            $query->where('id_rol', request('rol'));
        }
        if (request('estado')) {
            $query->where('estado', request('estado'));
        }

        $usuarios = $query->orderBy('nombre')->paginate(15)->withQueryString();
        $roles = Rol::orderBy('nombre')->get();

        $stats = [
            'total'      => Usuario::count(),
            'activos'    => Usuario::where('estado', 'activo')->count(),
            'inactivos'  => Usuario::where('estado', 'inactivo')->count(),
            'admins'     => Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'admin'))->count(),
            'conectados' => Usuario::where('ultimo_acceso', '>=', now()->subMinutes(Usuario::MINUTOS_CONECTADO))->count(),
        ];

        return view('usuarios.index', compact('usuarios', 'roles', 'stats'));
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

    public function toggleEstado(Usuario $usuario) {
        if (auth()->id() === $usuario->id) {
            return back()->withErrors(['error' => 'No puedes desactivar tu propia cuenta mientras tienes la sesión abierta.']);
        }

        $nuevoEstado = $usuario->estado === 'activo' ? 'inactivo' : 'activo';
        $usuario->update(['estado' => $nuevoEstado]);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario ' . ($nuevoEstado === 'activo' ? 'activado' : 'desactivado') . '.');
    }

    public function destroy(Usuario $usuario) {
        if (auth()->id() === $usuario->id) {
            return back()->withErrors(['error' => 'No puedes eliminar tu propia cuenta.']);
        }

        // Medida de seguridad: para eliminar un usuario primero hay que desactivarlo.
        // Así se evita borrar por error una cuenta que todavía está en uso.
        if ($usuario->estado === 'activo') {
            return back()->withErrors([
                'error' => "Por seguridad, primero debes desactivar a \"{$usuario->nombre}\" antes de poder eliminarlo. Usa el interruptor de la tarjeta para desactivarlo.",
            ]);
        }

        // Aun estando desactivado, si tiene historial asociado no se puede eliminar
        // sin romper esos registros (ventas, compras, producción, kardex, tiempos, etc. lo referencian).
        $usos = [];
        if ($usuario->ventas()->exists())      $usos[] = 'tiene ventas registradas';
        if ($usuario->compras()->exists())     $usos[] = 'tiene compras registradas';
        if ($usuario->producciones()->exists())$usos[] = 'tiene producciones registradas';
        if ($usuario->movimientos()->exists()) $usos[] = 'tiene movimientos de inventario registrados';
        if ($usuario->kardex()->exists())      $usos[] = 'tiene movimientos de productos (kardex) registrados';
        if ($usuario->tiempos()->exists())     $usos[] = 'tiene registros de tiempos de operación';

        if (!empty($usos)) {
            return back()->withErrors([
                'error' => "No se puede eliminar a \"{$usuario->nombre}\" porque " . implode(', ', $usos) .
                           ". Permanecerá desactivado para conservar el historial.",
            ]);
        }

        $this->sincronizarPermisos($usuario, []); // limpia sus permisos antes de borrar
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', "Usuario \"{$usuario->nombre}\" eliminado.");
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
