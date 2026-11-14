<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de los empleados fuera de su horario de acceso.
 *
 * La validación en "LoginRequest" impide que un empleado inicie sesión fuera de
 * su horario, pero no cubre a quien ya tenía una sesión abierta ni la cookie de
 * "remember me". Este middleware se ejecuta en el grupo "web", por lo que
 * expulsa al usuario en la siguiente petición, sin importar por dónde entró.
 *
 * Los encargados nunca se bloquean. Los empleados sin horario asignado sí: el
 * horario es obligatorio para ellos.
 */
class EnsureWithinAccessSchedule
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isWithinAccessSchedule()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $user->accessDeniedMessage();

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => $message]);
    }
}
