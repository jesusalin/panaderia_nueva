<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Utilidades de los respaldos de la base de datos.
 *
 * Cada respaldo es un archivo en storage/app/backups. Sus datos extra (quién lo hizo, si fue
 * automático, una nota, qué contenía, si está protegido) se guardan en "indice.json" en la
 * MISMA carpeta y no en la base de datos: así no se pierden al restaurar otro respaldo.
 */
class Respaldos
{
    public const INDICE = 'indice.json';

    public const TIPOS = [
        'automatico'      => ['texto' => 'Automático',        'icono' => 'fa-robot',            'color' => '#2170a3'],
        'manual'          => ['texto' => 'Manual',            'icono' => 'fa-hand-pointer',     'color' => '#1e8e5a'],
        'antes_restaurar' => ['texto' => 'Antes de restaurar', 'icono' => 'fa-life-ring',       'color' => '#d4880f'],
        'subido'          => ['texto' => 'Subido',            'icono' => 'fa-cloud-arrow-up',   'color' => '#8e44ad'],
    ];

    /** Horas máximas entre respaldos automáticos antes de avisar que algo no está corriendo. */
    public const HORAS_AVISO = 26;

    public static function carpeta(): string
    {
        $c = config('backup.carpeta') ?: storage_path('app/backups');
        if (!File::exists($c)) File::makeDirectory($c, 0755, true);
        return $c;
    }

    public static function indice(): array
    {
        $ruta = self::carpeta() . '/' . self::INDICE;
        if (!File::exists($ruta)) return [];
        $datos = json_decode((string) File::get($ruta), true);
        return is_array($datos) ? $datos : [];
    }

    public static function guardarIndice(array $indice): void
    {
        File::put(self::carpeta() . '/' . self::INDICE, json_encode($indice, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function anotar(string $archivo, array $datos): void
    {
        $indice = self::indice();
        $indice[$archivo] = array_merge($indice[$archivo] ?? [], $datos);
        self::guardarIndice($indice);
    }

    public static function olvidar(string $archivo): void
    {
        $indice = self::indice();
        unset($indice[$archivo]);
        self::guardarIndice($indice);
    }

    /** Archivos de respaldo (sin el índice), del más nuevo al más viejo, con sus datos. */
    public static function lista()
    {
        $indice = self::indice();
        return collect(File::files(self::carpeta()))
            ->filter(fn ($f) => $f->getFilename() !== self::INDICE && preg_match('/\.(zip|sql\.gz)$/', $f->getFilename()))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(function ($f) use ($indice) {
                $n = $f->getFilename();
                $meta = $indice[$n] ?? [];
                return [
                    'nombre'    => $n,
                    'bytes'     => $f->getSize(),
                    'tamano'    => self::tamano($f->getSize()),
                    'fecha'     => Carbon::createFromTimestamp($f->getMTime(), config('app.timezone')),
                    'motor'     => str_contains($n, 'mysql') ? 'mysql' : 'sqlite',
                    'tipo'      => $meta['tipo'] ?? (str_contains($n, 'subido') ? 'subido' : 'automatico'),
                    'usuario'   => $meta['usuario'] ?? null,
                    'nota'      => $meta['nota'] ?? null,
                    'protegido' => (bool) ($meta['protegido'] ?? false),
                    'resumen'   => $meta['resumen'] ?? null,
                    'verificado'=> $meta['verificado'] ?? null,
                    'restaurado'=> $meta['restaurado'] ?? null,
                ];
            })->values();
    }

    /** Qué hay en la base de datos ahora (se guarda junto al respaldo para saber qué contiene). */
    public static function resumenActual(): array
    {
        $contar = function (string $tabla, ?callable $filtro = null) {
            try {
                $q = DB::table($tabla);
                if ($filtro) $filtro($q);
                return $q->count();
            } catch (\Throwable $e) { return null; }
        };
        try { $ultima = DB::table('ventas')->max('fecha_venta'); } catch (\Throwable $e) { $ultima = null; }
        return [
            'ventas'       => $contar('ventas', fn ($q) => $q->where('estado', 'completada')),
            'compras'      => $contar('compras'),
            'producciones' => $contar('producciones'),
            'productos'    => $contar('productos'),
            'insumos'      => $contar('materia_prima'),
            'clientes'     => $contar('clientes'),
            'usuarios'     => $contar('usuarios'),
            'ultima_venta' => $ultima,
        ];
    }

    /**
     * Revisa que el archivo se pueda usar para restaurar: el .zip debe traer una base SQLite
     * válida y el .sql.gz debe descomprimirse y tener tablas. Devuelve [ok, mensaje].
     */
    public static function verificar(string $ruta): array
    {
        if (str_ends_with($ruta, '.zip')) {
            $zip = new \ZipArchive();
            if ($zip->open($ruta) !== true) return [false, 'El .zip está dañado o no se puede abrir.'];
            $contenido = $zip->getFromName('database.sqlite');
            $zip->close();
            if ($contenido === false) return [false, 'El .zip no trae el archivo database.sqlite.'];
            if (!str_starts_with($contenido, 'SQLite format 3')) return [false, 'El archivo de adentro no es una base de datos SQLite.'];
            return [true, 'Base SQLite válida (' . self::tamano(strlen($contenido)) . ' sin comprimir).'];
        }
        if (str_ends_with($ruta, '.sql.gz')) {
            $sql = @gzdecode((string) File::get($ruta));
            if ($sql === false) return [false, 'El .sql.gz está dañado: no se puede descomprimir.'];
            $tablas = preg_match_all('/CREATE TABLE/i', $sql);
            if (!$tablas) return [false, 'El archivo no tiene tablas (CREATE TABLE).'];
            return [true, "Volcado MySQL válido con {$tablas} tabla(s)."];
        }
        return [false, 'Tipo de archivo no reconocido (debe ser .zip o .sql.gz).'];
    }

    /** Ruta del archivo si existe dentro de la carpeta de respaldos (evita ../../). */
    public static function ruta(string $archivo): ?string
    {
        $nombre = basename($archivo);
        if ($nombre === self::INDICE) return null;
        $ruta = self::carpeta() . '/' . $nombre;
        return File::exists($ruta) ? $ruta : null;
    }

    public static function tamano(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
