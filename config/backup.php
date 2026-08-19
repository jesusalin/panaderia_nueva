<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retención de respaldos
    |--------------------------------------------------------------------------
    | Cuántos respaldos conservar en storage/app/backups antes de que
    | "php artisan backup:run" empiece a borrar los más antiguos.
    | Con un respaldo diario, 14 equivale a dos semanas de historial.
    */
    'mantener' => env('BACKUP_MANTENER', 14),

    /*
    |--------------------------------------------------------------------------
    | Rutas de los binarios de MySQL
    |--------------------------------------------------------------------------
    | Normalmente "mysqldump" y "mysql" alcanzan si están en el PATH del
    | sistema. En instalaciones con XAMPP/Laragon en Windows a veces hace
    | falta la ruta completa, por ejemplo:
    | C:\xampp\mysql\bin\mysqldump.exe
    */
    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
    'mysql_path'      => env('BACKUP_MYSQL_PATH', 'mysql'),

];
