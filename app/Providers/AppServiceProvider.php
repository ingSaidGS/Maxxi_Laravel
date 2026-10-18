<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Capacidades por rol (Opción A: tipo + Gates). Solo el encargado puede
        // administrar catálogo, compras, reportes y usuarios. El empleado opera
        // ventas, notas de venta y preparación (picking), que no requieren gate.
        $soloEncargado = static fn (User $user): bool => $user->isEncargado();

        Gate::define('manage-catalog', $soloEncargado);
        Gate::define('manage-purchases', $soloEncargado);
        Gate::define('view-reports', $soloEncargado);
        Gate::define('manage-users', $soloEncargado);
    }
}
