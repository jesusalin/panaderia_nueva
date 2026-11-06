@echo off
REM Programador de Laravel para Muruwasi (respaldo diario 3:00 a.m. y ordenes automaticas 6:30 a.m.).
REM El Programador de tareas de Windows debe ejecutar este archivo CADA MINUTO.
REM Si "php" no se reconoce, cambia la linea de abajo por la ruta completa, por ejemplo:
REM "C:\xampp\php\php.exe" artisan schedule:run
cd /d "%~dp0"
php artisan schedule:run >> "storage\logs\programador.log" 2>&1
