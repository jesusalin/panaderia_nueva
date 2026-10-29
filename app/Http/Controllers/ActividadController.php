<?php

namespace App\Http\Controllers;

use App\Models\ActividadUsuario;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Historial de actividad (solo admin): quién hizo qué y cuándo.
 * Filtros: usuario, tipo de acción, módulo, fechas y texto.
 */
class ActividadController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'usuario' => 'nullable|integer',
            'accion'  => 'nullable|in:' . implode(',', array_keys(ActividadUsuario::ACCIONES)),
            'modulo'  => 'nullable|string|max:40',
            'desde'   => 'nullable|date',
            'hasta'   => 'nullable|date',
            'buscar'  => 'nullable|string|max:100',
        ]);

        $query = ActividadUsuario::with('usuario.rol');
        if ($request->filled('usuario')) $query->where('id_usuario', $request->usuario);
        if ($request->filled('accion'))  $query->where('accion', $request->accion);
        if ($request->filled('modulo'))  $query->where('modulo', $request->modulo);
        if ($request->filled('desde'))   $query->where('created_at', '>=', Carbon::parse($request->desde)->startOfDay());
        if ($request->filled('hasta'))   $query->where('created_at', '<=', Carbon::parse($request->hasta)->endOfDay());
        if ($request->filled('buscar'))  $query->where('descripcion', 'like', '%' . $request->buscar . '%');

        $actividades = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(40)->withQueryString();

        // Ficha del usuario elegido
        $usuarioSel = $request->filled('usuario') ? Usuario::with('rol')->find($request->usuario) : null;
        $resumenUsuario = null;
        if ($usuarioSel) {
            $base = ActividadUsuario::where('id_usuario', $usuarioSel->id);
            $resumenUsuario = [
                'acciones30'  => (clone $base)->where('created_at', '>=', now()->subDays(30))->whereNotIn('accion', ['inicio_sesion', 'cierre_sesion', 'login_fallido'])->count(),
                'sesiones30'  => (clone $base)->where('created_at', '>=', now()->subDays(30))->where('accion', 'inicio_sesion')->count(),
                'fallidos30'  => (clone $base)->where('created_at', '>=', now()->subDays(30))->where('accion', 'login_fallido')->count(),
                'primera'     => (clone $base)->min('created_at'),
            ];
        }

        // Aviso de seguridad: intentos fallidos de los últimos 7 días
        $fallidosSemana = ActividadUsuario::where('accion', 'login_fallido')->where('created_at', '>=', now()->subDays(7))->count();

        $usuarios = Usuario::orderBy('nombre')->get(['id', 'nombre', 'usuario']);
        $modulos  = ActividadUsuario::whereNotNull('modulo')->distinct()->orderBy('modulo')->pluck('modulo');

        return view('actividad.index', compact('actividades', 'usuarios', 'modulos', 'usuarioSel', 'resumenUsuario', 'fallidosSemana'));
    }
}
