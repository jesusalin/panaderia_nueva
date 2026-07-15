<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.app', function ($view) {
            $view->with('stockBajoProductosCount', Producto::whereColumn('stock_actual', '<=', 'stock_minimo')->count());
            $view->with('stockBajoMateriaPrimaCount', MateriaPrima::whereColumn('stock_actual', '<=', 'stock_minimo')->count());
        });
    }
}
