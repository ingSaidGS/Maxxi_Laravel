<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra la sesión de los usuarios desactivados.
 *
 * La validación en "LoginRequest" impide que una cuenta inactiva inicie sesión,
 * pero no cubre a quien ya tenía una sesión abierta ni la cookie de "remember me".
 * Este middleware se ejecuta en el grupo "web", por lo que expulsa al usuario en
 * la siguiente petición, sin importar por dónde entró.
 */
class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * Solo se bloquea cuando "active" es explícitamente false. La columna es
     * NOT NULL con default true, así que un null únicamente significa que el
     * atributo no venía cargado en el modelo (p. ej. un usuario recién creado
     * en memoria), no que la cuenta esté desactivada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->active !== false) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta al encargado.',
            ], 403);
        }

        return redirect()
            ->route('login')
            ->withErrors(['email' => 'Tu cuenta está desactivada. Contacta al encargado.']);
    }
}
