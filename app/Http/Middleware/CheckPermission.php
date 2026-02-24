<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permKeys): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        if ($user->is_super_admin) {
            return $next($request);
        }

        $hasAny = collect($permKeys)->contains(fn($k) => $user->hasPermission($k));

        if (!$hasAny) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
