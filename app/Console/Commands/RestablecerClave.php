<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;

/**
 * Por si el ADMIN olvida su contraseña (y no hay otro admin que se la cambie):
 *   php artisan usuario:clave admin
 * Le pone una contraseña temporal y al entrar el sistema le pide elegir una nueva.
 */
class RestablecerClave extends Command
{
    protected $signature = 'usuario:clave {usuario : Nombre de usuario (ej. admin)}';
    protected $description = 'Pone una contraseña temporal a un usuario que olvidó la suya';

    public function handle(): int
    {
        $u = Usuario::where('usuario', $this->argument('usuario'))->first();
        if (!$u) {
            $this->error('No existe el usuario "' . $this->argument('usuario') . '".');
            return self::FAILURE;
        }

        $clave = Usuario::generarClaveTemporal();
        $u->update(['password' => $clave, 'debe_cambiar_password' => true, 'estado' => 'activo']);

        $this->info("Contraseña temporal de {$u->nombre} ({$u->usuario}): {$clave}");
        $this->line('Al entrar, el sistema le pedirá elegir una contraseña nueva.');
        return self::SUCCESS;
    }
}
