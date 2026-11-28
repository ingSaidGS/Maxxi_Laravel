<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Recuerda la última URL de un listado paginado (página, filtros y cantidad
 * por página) para volver a él desde la ficha, la edición o tras una acción,
 * sin perder la posición.
 *
 * El controlador que lo usa declara:
 * - INDEX_URL_SESSION_KEY: clave de sesión donde se guarda la URL (las vistas
 *   la leen con session(Controlador::INDEX_URL_SESSION_KEY, route(...))).
 * - INDEX_ROUTE: nombre de la ruta del listado, usada si no hay URL guardada.
 */
trait RemembersIndexUrl
{
    /**
     * Guarda la URL actual del listado en sesión.
     *
     * Si la página pedida quedó fuera de rango (por ejemplo, al dar de baja el
     * único registro de la última página), devuelve una redirección a la última
     * página con resultados, conservando los filtros; en ese caso no guarda la
     * URL, para no recordar una página vacía.
     */
    protected function rememberIndexUrl(Request $request, LengthAwarePaginator $paginator): ?RedirectResponse
    {
        if ($paginator->currentPage() > $paginator->lastPage()) {
            return redirect()->to($paginator->url($paginator->lastPage()));
        }

        $request->session()->put(static::INDEX_URL_SESSION_KEY, $request->fullUrl());

        return null;
    }

    /**
     * Última URL del listado guardada en sesión, o el listado sin filtros si no
     * hay ninguna (por ejemplo, al entrar directo a una ficha).
     */
    protected function indexUrl(): string
    {
        return session(static::INDEX_URL_SESSION_KEY, route(static::INDEX_ROUTE));
    }
}
