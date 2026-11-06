<?php

namespace App\Http\Controllers;

use App\Support\Respaldos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Respaldos de la base de datos (solo admin). Generar con nota, descargar, verificar,
 * proteger de la limpieza automática, subir uno desde la computadora y restaurar.
 * Restaurar es destructivo: hay que escribir RESTAURAR y antes se hace un respaldo
 * automático "antes de restaurar" para poder deshacerlo.
 */
class BackupController extends Controller
{
    public const PALABRA = 'RESTAURAR';

    public function index(Request $request)
    {
        $todos = Respaldos::lista();
        $motor = config('database.default');
        $filtro = array_key_exists($request->tipo, Respaldos::TIPOS) ? $request->tipo : null;
        $backups = $filtro ? $todos->where('tipo', $filtro)->values() : $todos;

        $ultimo = $todos->first();
        $ultimoAuto = $todos->firstWhere('tipo', 'automatico');
        $horas = $ultimo ? $ultimo['fecha']->diffInHours(now()) : null;
        $stats = [
            'total'       => $todos->count(),
            'espacio'     => Respaldos::tamano((int) $todos->sum('bytes')),
            'ultimo'      => $ultimo,
            'horas'       => $horas,
            'salud'       => !$ultimo ? 'mal' : ($horas <= Respaldos::HORAS_AVISO ? 'bien' : ($horas <= 72 ? 'atencion' : 'mal')),
            'auto_ok'     => $ultimoAuto && $ultimoAuto['fecha']->diffInHours(now()) <= Respaldos::HORAS_AVISO,
            'ultimo_auto' => $ultimoAuto,
            'protegidos'  => $todos->where('protegido', true)->count(),
            'motorActivo' => $motor,
            'seConservan' => (int) config('backup.mantener', 14),
            'ahora'       => Respaldos::resumenActual(),
            'porTipo'     => $todos->countBy('tipo'),
        ];
        $porDia = $backups->groupBy(fn ($b) => $b['fecha']->toDateString());

        return view('backups.index', compact('backups', 'porDia', 'stats', 'filtro'));
    }

    public function store(Request $request)
    {
        $request->validate(['nota' => 'nullable|string|max:150']);
        $salida = Artisan::call('backup:run', [
            '--tipo' => 'manual', '--nota' => $request->nota, '--usuario' => auth()->user()?->nombre,
        ]);

        if ($salida !== 0) {
            return back()->with('error', 'No se pudo generar el respaldo. ' . trim(Artisan::output()) . ' Revisa storage/logs/laravel.log.');
        }

        return back()->with('success', 'Respaldo generado correctamente.');
    }

    public function download(string $archivo)
    {
        $ruta = Respaldos::ruta($archivo) ?? abort(404);
        return response()->download($ruta);
    }

    /** Revisa que el archivo esté sano y se pueda usar para restaurar. */
    public function verificar(string $archivo)
    {
        $ruta = Respaldos::ruta($archivo) ?? abort(404);
        [$ok, $mensaje] = Respaldos::verificar($ruta);
        Respaldos::anotar(basename($ruta), ['verificado' => ['ok' => $ok, 'mensaje' => $mensaje, 'fecha' => now()->toDateTimeString()]]);
        return back()->with($ok ? 'success' : 'error', ($ok ? 'El respaldo está bien: ' : 'Problema con el respaldo: ') . $mensaje);
    }

    /** Protegido = no se borra en la limpieza automática ni se puede eliminar sin desprotegerlo. */
    public function proteger(string $archivo)
    {
        $ruta = Respaldos::ruta($archivo) ?? abort(404);
        $nombre = basename($ruta);
        $actual = (bool) (Respaldos::indice()[$nombre]['protegido'] ?? false);
        Respaldos::anotar($nombre, ['protegido' => !$actual]);
        return back()->with('success', !$actual ? 'Respaldo protegido: no se borrará en la limpieza automática.' : 'El respaldo ya no está protegido.');
    }

    public function nota(Request $request, string $archivo)
    {
        $ruta = Respaldos::ruta($archivo) ?? abort(404);
        $request->validate(['nota' => 'nullable|string|max:150']);
        Respaldos::anotar(basename($ruta), ['nota' => $request->nota ?: null]);
        return back()->with('success', 'Nota guardada.');
    }

    public function destroy(string $archivo)
    {
        $ruta = Respaldos::ruta($archivo) ?? abort(404);
        $nombre = basename($ruta);
        if (Respaldos::indice()[$nombre]['protegido'] ?? false) {
            return back()->with('error', 'Ese respaldo está protegido. Quítale la protección para poder eliminarlo.');
        }
        File::delete($ruta);
        Respaldos::olvidar($nombre);
        return back()->with('success', 'Respaldo eliminado.');
    }

    /** Subir un respaldo descargado antes (por ejemplo, para pasar los datos a otra computadora). */
    public function subir(Request $request)
    {
        $request->validate(['archivo' => 'required|file|max:204800', 'nota' => 'nullable|string|max:150'], [
            'archivo.required' => 'Elige el archivo del respaldo.', 'archivo.max' => 'El archivo pesa más de 200 MB.',
        ]);
        $original = $request->file('archivo')->getClientOriginalName();
        $motor = config('database.default');
        $ext = str_ends_with(strtolower($original), '.sql.gz') ? 'sql.gz' : (str_ends_with(strtolower($original), '.zip') ? 'zip' : null);
        if (!$ext) {
            return back()->withErrors(['archivo' => 'Debe ser un respaldo del sistema: un .zip (SQLite) o un .sql.gz (MySQL).']);
        }
        if (($ext === 'zip') !== ($motor === 'sqlite')) {
            return back()->withErrors(['archivo' => 'Ese respaldo es de ' . ($ext === 'zip' ? 'SQLite' : 'MySQL') . ' y el sistema usa ' . strtoupper($motor) . '.']);
        }
        // Se copia con su extensión para poder revisarlo (el archivo subido llega sin extensión)
        $temporal = Respaldos::carpeta() . '/.subida-' . uniqid() . '.' . $ext;
        File::copy($request->file('archivo')->getRealPath(), $temporal);
        [$ok, $mensaje] = Respaldos::verificar($temporal);
        if (!$ok) {
            File::delete($temporal);
            return back()->withErrors(['archivo' => 'El archivo no sirve: ' . $mensaje]);
        }
        $nombre = 'backup-' . $motor . '-' . now()->format('Y-m-d_His') . '-subido.' . $ext;
        File::move($temporal, Respaldos::carpeta() . '/' . $nombre);
        Respaldos::anotar($nombre, [
            'tipo' => 'subido', 'usuario' => auth()->user()?->nombre, 'protegido' => false,
            'nota' => $request->nota ?: 'Archivo: ' . mb_substr($original, 0, 120),
            'verificado' => ['ok' => true, 'mensaje' => $mensaje, 'fecha' => now()->toDateTimeString()],
            'creado' => now()->toDateTimeString(),
        ]);
        return back()->with('success', 'Respaldo subido y verificado. Ya puedes restaurarlo desde la lista.');
    }

    /**
     * Reemplaza TODOS los datos por los del respaldo. Hay que escribir RESTAURAR, se revisa
     * el archivo y antes se guarda un respaldo "antes de restaurar" para poder volver atrás.
     */
    public function restore(Request $request, string $archivo)
    {
        $ruta = Respaldos::ruta($archivo) ?? abort(404);
        $nombre = basename($ruta);
        if (mb_strtoupper(trim((string) $request->confirmar)) !== self::PALABRA) {
            return back()->with('error', 'Para restaurar escribe la palabra ' . self::PALABRA . '.');
        }
        [$ok, $mensaje] = Respaldos::verificar($ruta);
        if (!$ok) {
            return back()->with('error', 'No se restauró: ' . $mensaje);
        }

        $usuario = auth()->user()?->nombre;
        $seguridad = Artisan::call('backup:run', [
            '--tipo' => 'antes_restaurar', '--sin-rotacion' => true, '--usuario' => $usuario,
            '--nota' => 'Copia automática antes de restaurar ' . $nombre,
        ]);
        if ($seguridad !== 0) {
            return back()->with('error', 'No se restauró porque no se pudo hacer la copia de seguridad previa. ' . trim(Artisan::output()));
        }

        $salida = Artisan::call('backup:restore', ['archivo' => $ruta, '--force' => true]);
        if ($salida !== 0) {
            return back()->with('error', 'No se pudo restaurar el respaldo. ' . trim(Artisan::output()) . ' Tus datos no cambiaron.');
        }
        Respaldos::anotar($nombre, ['restaurado' => ['fecha' => now()->toDateTimeString(), 'usuario' => $usuario]]);

        return redirect()->route('backups.index')->with('success', 'Base de datos restaurada desde el respaldo del ' . \Illuminate\Support\Carbon::createFromTimestamp(File::lastModified($ruta), config('app.timezone'))->format('d/m/Y H:i')
            . '. Si te equivocaste, restaura el respaldo "Antes de restaurar" que se acaba de crear.');
    }
}
