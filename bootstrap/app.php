<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureWithinAccessSchedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // Expulsa a los usuarios desactivados en cualquier petición del grupo
        // "web" (incluye sesiones abiertas y la cookie de "remember me").
        $middleware->web(append: [EnsureUserIsActive::class]);

        // Expulsa a los empleados fuera de su horario de acceso. Va después del
        // de "active" para que una cuenta desactivada se cierre primero.
        $middleware->web(append: [EnsureWithinAccessSchedule::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
