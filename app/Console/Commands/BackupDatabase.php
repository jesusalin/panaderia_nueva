<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Genera un respaldo de la base de datos activa (SQLite o MySQL) y lo guarda
 * en storage/app/backups. Pensado para correr manualmente (php artisan
 * backup:run) o programado (ver routes/console.php, corre todos los días).
 *
 * No depende de paquetes externos: para SQLite simplemente copia el archivo
 * .sqlite, y para MySQL usa el binario mysqldump que ya viene con cualquier
 * instalación de MySQL/XAMPP/Laragon.
 */
class BackupDatabase extends Command
{
    protected $signature = 'backup:run
        {--sin-rotacion : No borrar respaldos antiguos aunque se pase el límite configurado}';

    protected $description = 'Genera un respaldo de la base de datos (SQLite o MySQL) y aplica rotación automática';

    public function handle(): int
    {
        $driver = config('database.default');
        $carpeta = storage_path('app/backups');

        if (! File::exists($carpeta)) {
            File::makeDirectory($carpeta, 0755, true);
        }

        $timestamp = now()->format('Y-m-d_His');

        $resultado = match ($driver) {
            'sqlite' => $this->respaldarSqlite($carpeta, $timestamp),
            'mysql'  => $this->respaldarMysql($carpeta, $timestamp),
            default  => null,
        };

        if ($resultado === null) {
            $this->error("El motor de base de datos '{$driver}' no está soportado para respaldo automático (solo sqlite y mysql).");
            return self::FAILURE;
        }

        if ($resultado === false) {
            $this->error('No se pudo generar el respaldo. Revisa el log para más detalle.');
            return self::FAILURE;
        }

        $this->info("Respaldo generado: {$resultado}");

        if (! $this->option('sin-rotacion')) {
            $eliminados = $this->rotarRespaldosAntiguos($carpeta);
            if ($eliminados > 0) {
                $this->line("Rotación: se eliminaron {$eliminados} respaldo(s) antiguo(s) (se conservan los últimos " . config('backup.mantener', 14) . ").");
            }
        }

        return self::SUCCESS;
    }

    /**
     * SQLite: el "respaldo" es simplemente una copia del archivo de base de
     * datos completo, comprimida en .zip para no ocupar espacio de más.
     */
    private function respaldarSqlite(string $carpeta, string $timestamp): string|false
    {
        $origen = config('database.connections.sqlite.database');

        if (! File::exists($origen)) {
            logger()->error("backup:run — no se encontró el archivo SQLite en {$origen}");
            return false;
        }

        $destino = "{$carpeta}/backup-sqlite-{$timestamp}.zip";

        $zip = new \ZipArchive();
        if ($zip->open($destino, \ZipArchive::CREATE) !== true) {
            logger()->error("backup:run — no se pudo crear el archivo zip en {$destino}");
            return false;
        }

        $zip->addFile($origen, 'database.sqlite');
        $zip->close();

        return $destino;
    }

    /**
     * MySQL: ejecuta mysqldump y comprime la salida con gzip al vuelo, así el
     * archivo .sql.gz nunca queda gigante en un negocio con mucho movimiento.
     */
    private function respaldarMysql(string $carpeta, string $timestamp): string|false
    {
        $conexion = config('database.connections.mysql');
        $destino  = "{$carpeta}/backup-mysql-{$timestamp}.sql.gz";
        $mysqldump = config('backup.mysqldump_path', 'mysqldump');

        // La contraseña se pasa por un archivo de credenciales temporal
        // (--defaults-extra-file) en vez de la variable de entorno MYSQL_PWD.
        // En Windows, forzar un entorno personalizado al proceso hijo puede
        // omitir variables internas del sistema (p. ej. SystemRoot), lo que
        // impide que Winsock inicialice sockets TCP/IP dentro de ese proceso
        // y produce el error "Can't create TCP/IP socket (10106)" aunque el
        // mismo comando funcione perfecto ejecutado manualmente en la terminal.
        $credenciales = tempnam(sys_get_temp_dir(), 'bkpcnf');
        file_put_contents($credenciales, "[client]\npassword={$conexion['password']}\n");

        $comando = [
            $mysqldump,
            '--defaults-extra-file=' . $credenciales,
            '--host=' . $conexion['host'],
            '--port=' . $conexion['port'],
            '--user=' . $conexion['username'],
            '--single-transaction',
            '--skip-lock-tables',
            $conexion['database'],
        ];

        $resultado = Process::timeout(300)->run($comando);

        File::delete($credenciales);

        if ($resultado->failed()) {
            logger()->error('backup:run — falló mysqldump: ' . $resultado->errorOutput());
            return false;
        }

        $volcado = gzencode($resultado->output(), 9);
        if ($volcado === false || File::put($destino, $volcado) === false) {
            logger()->error('backup:run — no se pudo escribir el archivo comprimido de respaldo');
            return false;
        }

        return $destino;
    }

    /**
     * Conserva solo los N respaldos más recientes (configurable vía
     * BACKUP_MANTENER en .env, por defecto 14) para no llenar el disco.
     */
    private function rotarRespaldosAntiguos(string $carpeta): int
    {
        $limite = (int) config('backup.mantener', 14);

        $archivos = collect(File::files($carpeta))
            ->sortByDesc(fn ($archivo) => $archivo->getMTime())
            ->values();

        $sobrantes = $archivos->slice($limite);

        foreach ($sobrantes as $archivo) {
            File::delete($archivo->getPathname());
        }

        return $sobrantes->count();
    }
}
