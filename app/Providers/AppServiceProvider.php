<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Política de contraseñas del sistema: aplica al perfil, al
        // restablecimiento y al CRUD de usuarios (todos usan Password::defaults()).
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Capacidades por rol (Opción A: tipo + Gates).
        //
        // - "manage-catalog":  unidades, productos y presentaciones en modo
        //   gestión (crear, editar, ajustar stock y bajas lógicas). El empleado
        //   solo puede ver el catálogo.
        // - "manage-sales":     editar/anular ventas y cobrar fiadas. El
        //   empleado puede listar, ver y crear ventas (incluido cobrar en el
        //   momento vía store).
        // - "manage-sale-notes": todo en notas de venta salvo ver la ficha
        //   ("show"); el empleado solo llega al show desde la hoja de
        //   alistamiento (sale-preparations).
        // - "manage-purchases": editar/anular compras. El empleado puede listar,
        //   ver y crear compras.
        // - "view-reports" y "manage-users": exclusivos del encargado.
        //
        // Preparación/picking (sale-preparations) no requiere gate: ambos roles.
        $soloEncargado = static fn (User $user): bool => $user->isEncargado();

        Gate::define('manage-catalog', $soloEncargado);
        Gate::define('manage-sales', $soloEncargado);
        Gate::define('manage-sale-notes', $soloEncargado);
        Gate::define('manage-purchases', $soloEncargado);
        Gate::define('view-reports', $soloEncargado);
        Gate::define('manage-users', $soloEncargado);
    }
}
