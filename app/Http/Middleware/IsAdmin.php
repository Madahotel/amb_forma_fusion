<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Accès réservé aux administrateurs'], 403);
        }

        return $next($request);
    }
}
