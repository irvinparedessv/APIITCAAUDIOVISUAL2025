<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->user() || !$request->user()->role || !in_array(strtolower($request->user()->role->nombre), array_map('strtolower', $roles))) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return $next($request);
    }
}
