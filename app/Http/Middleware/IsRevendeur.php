<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsRevendeur
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->role !== 'revendeur') {
            return response()->json(['message' => 'Accès réservé aux revendeurs'], 403);
        }

        return $next($request);
    }
}
