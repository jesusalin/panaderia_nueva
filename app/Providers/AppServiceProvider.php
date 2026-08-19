<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use App\Models\Producto;
use App\Models\MateriaPrima;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Usa nuestra propia vista de paginación (Bootstrap por defecto de Laravel
        // usa clases de Tailwind, que no existen en este proyecto con AdminLTE).
        Paginator::defaultView('pagination.custom');
        Paginator::defaultSimpleView('pagination.custom');

        View::composer('layouts.app', function ($view) {
            $view->with('stockBajoProductosCount', Producto::whereColumn('stock_actual', '<=', 'stock_minimo')->count());
            $view->with('stockBajoMateriaPrimaCount', MateriaPrima::whereColumn('stock_actual', '<=', 'stock_minimo')->count());
        });
    }
}
