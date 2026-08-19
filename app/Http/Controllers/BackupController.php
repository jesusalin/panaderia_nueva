<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Pantalla de administración de respaldos de base de datos. Reservada para
 * admin (protegida con el mismo middleware que Usuarios en routes/web.php).
 * Permite generar un respaldo al vuelo, descargar los existentes, restaurar
 * uno (operación destructiva, con doble confirmación en el navegador) y
 * borrar los que ya no hagan falta.
 */
class BackupController extends Controller
{
    private function carpeta(): string
    {
        return storage_path('app/backups');
    }

    public function index()
    {
        $carpeta = $this->carpeta();
        $archivos = File::exists($carpeta) ? File::files($carpeta) : [];

        $backups = collect($archivos)
            ->sortByDesc(fn ($archivo) => $archivo->getMTime())
            ->map(fn ($archivo) => [
                'nombre' => $archivo->getFilename(),
                'tamano' => $this->formatearTamano($archivo->getSize()),
                'fecha'  => \Illuminate\Support\Carbon::createFromTimestamp($archivo->getMTime()),
                'motor'  => str_contains($archivo->getFilename(), 'mysql') ? 'MySQL' : 'SQLite',
            ])
            ->values();

        $espacioTotal = collect($archivos)->sum(fn ($archivo) => $archivo->getSize());

        $stats = [
            'total'       => $backups->count(),
            'espacio'     => $this->formatearTamano($espacioTotal),
            'ultimo'      => $backups->first()['fecha'] ?? null,
            'motorActivo' => config('database.default'),
            'seConservan' => config('backup.mantener', 14),
        ];

        return view('backups.index', compact('backups', 'stats'));
    }

    public function store()
    {
        $salida = Artisan::call('backup:run');

        if ($salida !== 0) {
            return back()->with('error', 'No se pudo generar el respaldo. Revisa storage/logs/laravel.log para más detalle.');
        }

        return back()->with('success', 'Respaldo generado correctamente.');
    }

    public function download(string $archivo)
    {
        $ruta = $this->rutaSegura($archivo);

        if (! $ruta) {
            abort(404);
        }

        return response()->download($ruta);
    }

    public function destroy(string $archivo)
    {
        $ruta = $this->rutaSegura($archivo);

        if (! $ruta) {
            abort(404);
        }

        File::delete($ruta);

        return back()->with('success', 'Respaldo eliminado.');
    }

    /**
     * Restaura la base de datos completa desde un respaldo. Operación
     * destructiva: reemplaza TODOS los datos actuales. La confirmación
     * fuerte ocurre en el navegador (ver backups/index.blade.php); aquí
     * solo se valida que el archivo exista y pertenezca a la carpeta de
     * respaldos antes de llamar al comando con --force.
     */
    public function restore(string $archivo)
    {
        $ruta = $this->rutaSegura($archivo);

        if (! $ruta) {
            abort(404);
        }

        $salida = Artisan::call('backup:restore', [
            'archivo' => $ruta,
            '--force' => true,
        ]);

        if ($salida !== 0) {
            return back()->with('error', 'No se pudo restaurar el respaldo. Revisa storage/logs/laravel.log para más detalle.');
        }

        return back()->with('success', "Base de datos restaurada desde \"{$archivo}\".");
    }

    /**
     * Evita path traversal (../../etc) validando que el nombre no tenga
     * separadores de ruta y que el archivo resultante viva dentro de la
     * carpeta de respaldos.
     */
    private function rutaSegura(string $archivo): ?string
    {
        $nombre = basename($archivo);
        $ruta = $this->carpeta() . '/' . $nombre;

        return File::exists($ruta) ? $ruta : null;
    }

    private function formatearTamano(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
