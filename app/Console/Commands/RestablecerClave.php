<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;

/**
 * Por si el ADMIN olvida su contraseña (y no hay otro admin que se la cambie):
 *   php artisan usuario:clave admin
 * Le pone una contraseña nueva y la muestra; luego puede cambiarla en Mi perfil.
 */
class RestablecerClave extends Command
{
    protected $signature = 'usuario:clave {usuario : Nombre de usuario (ej. admin)}';
    protected $description = 'Pone una contraseña nueva a un usuario que olvidó la suya';

    public function handle(): int
    {
        $u = Usuario::where('usuario', $this->argument('usuario'))->first();
        if (!$u) {
            $this->error('No existe el usuario "' . $this->argument('usuario') . '".');
            return self::FAILURE;
        }

        $clave = Usuario::generarClaveTemporal();
        $u->update(['password' => $clave, 'debe_cambiar_password' => false, 'estado' => 'activo']);

        $this->info("Contraseña nueva de {$u->nombre} ({$u->usuario}): {$clave}");
        $this->line('Anótala; el administrador puede cambiarla luego en Mi perfil.');
        return self::SUCCESS;
    }
}
