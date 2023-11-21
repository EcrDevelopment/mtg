<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AllowUnauthenticatedAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Permitir el acceso sin autenticación solo para la ruta de generación de PDF
        if ($request->is('certificado-anual/*')) {
            return $next($request);
        }

        // En caso contrario, redirigir a la página de inicio de sesión
        return redirect('/login');
    }
}
