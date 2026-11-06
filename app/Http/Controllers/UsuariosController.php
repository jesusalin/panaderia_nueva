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
        $query = Usuario::with(['rol', 'permisos', 'ultimaAccion'])
            ->withCount(['ventas', 'compras', 'producciones', 'movimientos', 'kardex'])
            ->withMax('ventas', 'fecha_venta');

        if (request('buscar')) {
            $buscar = request('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('apodo', 'like', "%{$buscar}%")
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

        // Filtro por actividad (según el último acceso)
        $actividad = request('actividad');
        // "hace menos de N días" contando días de calendario (igual que Usuario::estadoActividad)
        $hace = fn (int $dias) => now()->startOfDay()->subDays($dias - 1);
        match ($actividad) {
            'en_linea'      => $query->where('ultimo_acceso', '>=', now()->subMinutes(Usuario::MINUTOS_CONECTADO)),
            'activo'        => $query->where('ultimo_acceso', '>=', $hace(Usuario::DIAS_POCO_ACTIVO)),
            'poco_activo'   => $query->where('ultimo_acceso', '<', $hace(Usuario::DIAS_POCO_ACTIVO))
                                     ->where('ultimo_acceso', '>=', $hace(Usuario::DIAS_SIN_ACTIVIDAD)),
            'sin_actividad' => $query->where('ultimo_acceso', '<', $hace(Usuario::DIAS_SIN_ACTIVIDAD)),
            'nunca'         => $query->whereNull('ultimo_acceso'),
            default         => null,
        };

        $usuarios = $query->orderBy('nombre')->paginate(15)->withQueryString();
        $roles = Rol::orderBy('nombre')->get();

        $stats = [
            'total'      => Usuario::count(),
            'activos'    => Usuario::where('estado', 'activo')->count(),
            'inactivos'  => Usuario::where('estado', 'inactivo')->count(),
            'admins'     => Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'admin'))->count(),
            'conectados' => Usuario::where('ultimo_acceso', '>=', now()->subMinutes(Usuario::MINUTOS_CONECTADO))->count(),
            // Cuentas ACTIVAS que no se usan hace 30+ días (o nunca): candidatas a desactivar
            'sin_actividad' => Usuario::where('estado', 'activo')
                ->where(fn ($q) => $q->whereNull('ultimo_acceso')->orWhere('ultimo_acceso', '<', now()->startOfDay()->subDays(Usuario::DIAS_SIN_ACTIVIDAD - 1)))
                ->count(),
        ];

        return view('usuarios.index', compact('usuarios', 'roles', 'stats'));
    }

    public function create() {
        $roles = Rol::orderBy('nombre')->get();
        $modulos = Usuario::MODULOS;
        $existentes = $this->existentes();
        return view('usuarios.create', compact('roles', 'modulos', 'existentes'));
    }

    /** Usuarios y correos ya usados (para avisar mientras se escribe). */
    private function existentes(?Usuario $excepto = null): array
    {
        $otros = Usuario::when($excepto, fn ($q) => $q->where('id', '!=', $excepto->id))->get(['nombre', 'usuario', 'email']);
        return [
            'usuarios' => $otros->mapWithKeys(fn ($u) => [mb_strtolower($u->usuario) => $u->nombre])->all(),
            'emails'   => $otros->mapWithKeys(fn ($u) => [mb_strtolower($u->email) => $u->nombre])->all(),
        ];
    }

    /** ¿Quedaría el sistema sin ningún administrador activo si este usuario deja de serlo? */
    private function esUltimoAdmin(Usuario $usuario): bool
    {
        if (!$usuario->isAdmin() || $usuario->estado !== 'activo') return false;
        return !Usuario::where('id', '!=', $usuario->id)->where('estado', 'activo')
            ->whereHas('rol', fn ($q) => $q->where('nombre', 'admin'))->exists();
    }

    public function store(Request $request) {
        $request->merge(['usuario' => mb_strtolower(trim((string) $request->usuario)), 'email' => mb_strtolower(trim((string) $request->email))]);
        $request->validate([
            'nombre'=>'required|string|max:100',
            'apodo'=>'nullable|string|max:50',
            'usuario'=>'required|string|max:100|alpha_dash|unique:usuarios,usuario',
            'email'=>'required|email|unique:usuarios,email',
            'password'=>'required|min:6|confirmed',
            'id_rol'=>'required|exists:roles,id',
            'estado'=>'required|in:activo,inactivo',
            'permisos'=>'array',
            'permisos.*'=>'in:'.implode(',', Usuario::clavesPermiso()),
        ], [
            'usuario.alpha_dash' => 'El usuario solo puede tener letras, números, guiones y guiones bajos (sin espacios).',
            'usuario.unique'     => 'Ese nombre de usuario ya está en uso.',
            'email.unique'       => 'Ese correo ya lo usa otra cuenta.',
            'password.min'       => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
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

        return redirect()->route('usuarios.show', $usuario)->with('success', 'Usuario "' . $usuario->usuario . '" creado. Ya puede ingresar con su usuario y contraseña.');
    }

    /** Ficha del usuario: qué puede usar, qué hizo y cuándo entra. */
    public function show(Usuario $usuario) {
        $usuario->load(['rol', 'permisos']);
        $mes = now()->startOfMonth();
        $hace30 = now()->subDays(30);
        $trabajo = [
            'ventas_mes'   => $usuario->ventas()->where('estado', 'completada')->where('fecha_venta', '>=', $mes)->count(),
            'vendido_mes'  => (float) $usuario->ventas()->where('estado', 'completada')->where('fecha_venta', '>=', $mes)->sum('total'),
            'ventas'       => $usuario->ventas()->count(),
            'anuladas'     => \App\Models\Venta::where('id_usuario_anula', $usuario->id)->count(),
            'compras_mes'  => $usuario->compras()->where('created_at', '>=', $mes)->count(),
            'compras'      => $usuario->compras()->count(),
            'prod_mes'     => $usuario->producciones()->where('created_at', '>=', $mes)->count(),
            'producciones' => $usuario->producciones()->count(),
            'movs_mes'     => $usuario->movimientos()->where('created_at', '>=', $mes)->count() + $usuario->kardex()->where('created_at', '>=', $mes)->count(),
        ];
        $accesos = [
            'ingresos_30' => $usuario->actividades()->where('accion', 'inicio_sesion')->where('created_at', '>=', $hace30)->count(),
            'fallidos_30' => $usuario->actividades()->where('accion', 'login_fallido')->where('created_at', '>=', $hace30)->count(),
            'acciones_30' => $usuario->actividades()->whereNotIn('accion', ['inicio_sesion', 'cierre_sesion', 'login_fallido'])->where('created_at', '>=', $hace30)->count(),
            'ultimo_ip'   => $usuario->actividades()->where('accion', 'inicio_sesion')->latest('created_at')->value('ip'),
        ];
        // Acciones por día (últimos 14 días), sin contar entradas y salidas
        $porDia = $usuario->actividades()->whereNotIn('accion', ['inicio_sesion', 'cierre_sesion', 'login_fallido'])
            ->where('created_at', '>=', today()->subDays(13))->get(['created_at'])
            ->groupBy(fn ($a) => $a->created_at->toDateString())->map->count();
        $dias = collect(range(13, 0))->map(fn ($i) => ['dia' => today()->subDays($i), 'n' => $porDia[today()->subDays($i)->toDateString()] ?? 0]);
        $actividad = $usuario->actividades()->orderByDesc('created_at')->orderByDesc('id')->limit(15)->get();
        $ultimoAdmin = $this->esUltimoAdmin($usuario);

        return view('usuarios.show', compact('usuario', 'trabajo', 'accesos', 'dias', 'actividad', 'ultimoAdmin'));
    }

    public function edit(Usuario $usuario) {
        $roles = Rol::orderBy('nombre')->get();
        $modulos = Usuario::MODULOS;
        $permisosActuales = $usuario->modulosAsignados();
        $existentes = $this->existentes($usuario);
        $ultimoAdmin = $this->esUltimoAdmin($usuario);
        return view('usuarios.edit', compact('usuario', 'roles', 'modulos', 'permisosActuales', 'existentes', 'ultimoAdmin'));
    }

    public function update(Request $request, Usuario $usuario) {
        $request->merge(['usuario' => mb_strtolower(trim((string) $request->usuario)), 'email' => mb_strtolower(trim((string) $request->email))]);
        $request->validate([
            'nombre'=>'required|string|max:100',
            'apodo'=>'nullable|string|max:50',
            'usuario'=>'required|string|max:100|alpha_dash|unique:usuarios,usuario,'.$usuario->id,
            'email'=>'required|email|unique:usuarios,email,'.$usuario->id,
            'id_rol'=>'required|exists:roles,id',
            'estado'=>'required|in:activo,inactivo',
            'password'=>'nullable|min:6|confirmed',
            'permisos'=>'array',
            'permisos.*'=>'in:'.implode(',', Usuario::clavesPermiso()),
        ], [
            'usuario.alpha_dash' => 'El usuario solo puede tener letras, números, guiones y guiones bajos.',
            'usuario.unique'     => 'Ese nombre de usuario ya está en uso por otra persona.',
            'email.unique'       => 'Ese correo ya lo usa otra cuenta.',
            'password.min'       => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
        ]);

        // Seguridad: no quitarse a uno mismo el acceso, ni dejar el sistema sin administrador
        $seraAdmin = Rol::find($request->id_rol)?->nombre === 'admin';
        $pierdeAdmin = $usuario->isAdmin() && (!$seraAdmin || $request->estado !== 'activo');
        if (auth()->id() === $usuario->id && ($pierdeAdmin || $request->estado !== 'activo')) {
            return back()->withInput()->withErrors(['id_rol' => 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta. Pídeselo a otro administrador.']);
        }
        if ($pierdeAdmin && $this->esUltimoAdmin($usuario)) {
            return back()->withInput()->withErrors(['id_rol' => '"' . $usuario->nombre . '" es el único administrador activo. Primero crea o activa otro administrador.']);
        }

        $data = $request->only(['nombre','apodo','usuario','email','id_rol','estado']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            $data['debe_cambiar_password'] = false;
        }
        $usuario->update($data);

        $this->sincronizarPermisos($usuario, $request->input('permisos', []));

        return redirect()->to(\App\Support\Volver::url(route('usuarios.index')))->with('success','Usuario actualizado.');
    }

    /**
     * Cuando alguien olvida su contraseña: el admin le genera una nueva (se muestra una
     * sola vez). Solo el admin cambia contraseñas; los usuarios no pueden cambiar la suya.
     * Se guardan cifradas sin vuelta atrás (hash), así nadie —ni el admin— puede leerlas.
     */
    public function claveTemporal(Usuario $usuario) {
        if (auth()->id() === $usuario->id) {
            return back()->with('error', 'Tu propia contraseña la cambias en "Mi perfil".');
        }
        $clave = Usuario::generarClaveTemporal();
        $usuario->update(['password' => $clave, 'debe_cambiar_password' => false]);

        return back()->with('clave_temporal', ['nombre' => $usuario->nombre, 'usuario' => $usuario->usuario, 'clave' => $clave]);
    }

    public function toggleEstado(Usuario $usuario) {
        if (auth()->id() === $usuario->id) {
            return back()->withErrors(['error' => 'No puedes desactivar tu propia cuenta mientras tienes la sesión abierta.']);
        }

        if ($this->esUltimoAdmin($usuario)) {
            return back()->withErrors(['error' => '"' . $usuario->nombre . '" es el único administrador activo: si lo desactivas, nadie podría administrar el sistema.']);
        }
        $nuevoEstado = $usuario->estado === 'activo' ? 'inactivo' : 'activo';
        $usuario->update(['estado' => $nuevoEstado]);

        return back()
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

        if (!empty($usos)) {
            return back()->withErrors([
                'error' => "No se puede eliminar a \"{$usuario->nombre}\" porque " . implode(', ', $usos) .
                           ". Permanecerá desactivado para conservar el historial.",
            ]);
        }

        $this->sincronizarPermisos($usuario, []); // limpia sus permisos antes de borrar
        if ($usuario->foto_perfil) \Illuminate\Support\Facades\Storage::disk('public')->delete($usuario->foto_perfil);
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', "Usuario \"{$usuario->nombre}\" eliminado.");
    }

    /**
     * Reemplaza los permisos del usuario por la lista recibida del formulario
     * (una clave por pantalla: "inventario.conteo", "ventas", ...).
     */
    private function sincronizarPermisos(Usuario $usuario, array $modulos): void
    {
        $modulos = Usuario::expandirPermisos($modulos);

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
