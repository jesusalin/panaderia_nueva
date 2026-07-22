<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Restaura la base de datos a partir de un archivo generado por backup:run.
 * Deliberadamente solo por consola (no desde la web) porque es una operación
 * destructiva: reemplaza todos los datos actuales por los del respaldo.
 *
 * Uso:
 *   php artisan backup:restore storage/app/backups/backup-mysql-2026-07-22_030000.sql.gz
 *   php artisan backup:restore storage/app/backups/backup-sqlite-2026-07-22_030000.zip
 */
class RestoreDatabase extends Command
{
    protected $signature = 'backup:restore {archivo : Ruta del archivo de respaldo a restaurar}';

    protected $description = 'Restaura la base de datos desde un archivo generado por backup:run';

    public function handle(): int
    {
        $ruta = $this->argument('archivo');

        if (! File::exists($ruta)) {
            $this->error("No se encontró el archivo: {$ruta}");
            return self::FAILURE;
        }

        if (! $this->confirm("Esto reemplazará TODOS los datos actuales de la base de datos por los del respaldo '{$ruta}'. ¿Continuar?")) {
            $this->line('Restauración cancelada.');
            return self::SUCCESS;
        }

        $driver = config('database.default');

        $ok = match (true) {
            $driver === 'sqlite' && str_ends_with($ruta, '.zip') => $this->restaurarSqlite($ruta),
            $driver === 'mysql' && str_ends_with($ruta, '.sql.gz') => $this->restaurarMysql($ruta),
            default => null,
        };

        if ($ok === null) {
            $this->error("El archivo no corresponde al motor de base de datos activo ({$driver}) o tiene una extensión no reconocida.");
            return self::FAILURE;
        }

        if ($ok === false) {
            $this->error('Ocurrió un error al restaurar. Revisa el log para más detalle.');
            return self::FAILURE;
        }

        $this->info('Base de datos restaurada correctamente.');
        return self::SUCCESS;
    }

    private function restaurarSqlite(string $ruta): bool
    {
        $destino = config('database.connections.sqlite.database');

        $zip = new \ZipArchive();
        if ($zip->open($ruta) !== true) {
            logger()->error("backup:restore — no se pudo abrir el zip {$ruta}");
            return false;
        }

        $contenido = $zip->getFromName('database.sqlite');
        $zip->close();

        if ($contenido === false) {
            logger()->error("backup:restore — el zip {$ruta} no contiene database.sqlite");
            return false;
        }

        return File::put($destino, $contenido) !== false;
    }

    private function restaurarMysql(string $ruta): bool
    {
        $conexion = config('database.connections.mysql');
        $mysql = config('backup.mysql_path', 'mysql');

        $sql = gzdecode(File::get($ruta));
        if ($sql === false) {
            logger()->error("backup:restore — no se pudo descomprimir {$ruta}");
            return false;
        }

        $comando = [
            $mysql,
            '--host=' . $conexion['host'],
            '--port=' . $conexion['port'],
            '--user=' . $conexion['username'],
            $conexion['database'],
        ];

        $resultado = Process::env(['MYSQL_PWD' => $conexion['password']])
            ->timeout(300)
            ->input($sql)
            ->run($comando);

        if ($resultado->failed()) {
            logger()->error('backup:restore — falló mysql: ' . $resultado->errorOutput());
            return false;
        }

        return true;
    }
}
