<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Respaldo automático de la base de datos, todos los días a las 3:00 a.m.
// (hora de menor actividad). Requiere que el "scheduler" de Laravel esté
// corriendo — ver la nota en INSTRUCCIONES.md sobre el cron/tarea programada.
Schedule::command('backup:run')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer();

// Revisión automática de stock bajo: cada mañana deja generadas las órdenes
// de reposición de los insumos que estén por debajo de su mínimo, para que
// el almacenero las encuentre listas en "Órdenes Automáticas".
// (Requiere el mismo scheduler/cron que el respaldo — ver CAMBIOS-FALTANTES.md.)
Schedule::command('inventario:revisar-stock')
    ->dailyAt('06:30')
    ->withoutOverlapping();
