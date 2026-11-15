<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureWithinAccessSchedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
        // Error 419 (token CSRF vencido o inválido): ocurre si la sesión expiró
        // por inactividad o si la página quedó abierta desde antes. En vez de la
        // página genérica "Page Expired", se lleva a la página de bienvenida con
        // un aviso (y un token nuevo, para que el siguiente intento funcione).
        // Las peticiones AJAX/JSON reciben el mensaje con código 419.
        //
        // Laravel convierte TokenMismatchException en un HttpException 419 antes
        // de ejecutar estos callbacks, por eso se captura HttpException y se
        // filtra por el código (el resto de errores HTTP siguen su curso normal).
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $message = 'Tu sesión expiró. Vuelve a iniciar sesión. Si el problema continúa, abre el enlace en tu navegador (Chrome o Safari).';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            return redirect('/')->with('session_expired', $message);
        });
    })->create();
