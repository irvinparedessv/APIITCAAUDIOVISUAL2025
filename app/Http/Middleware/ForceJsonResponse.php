<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    // public function handle(Request $request, Closure $next)
    // {
    //     // Asegura que siempre se espere respuesta en formato JSON
    //     $request->headers->set('Accept', 'application/json');
        
    //     return $next($request);
    // }

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Solo forzar JSON si la respuesta es del tipo esperado
        if ($request->wantsJson() && $response instanceof JsonResponse) {
            return $response;
        }

        return $response;
    }
}
