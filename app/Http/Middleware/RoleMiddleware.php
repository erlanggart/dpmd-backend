<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Cek apakah user sudah login
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user = $request->user();

        // Parsing roles - jika roles hanya satu string dengan separator |
        $allowedRoles = [];
        foreach ($roles as $role) {
            if (str_contains($role, '|')) {
                $allowedRoles = array_merge($allowedRoles, explode('|', $role));
            } else {
                $allowedRoles[] = $role;
            }
        }

        // Cek apakah user memiliki salah satu role yang diizinkan
        if (in_array($user->role, $allowedRoles)) {
            return $next($request);
        }

        // Jika tidak memiliki role yang sesuai
        return response()->json(['message' => 'Forbidden'], 403);
    }
}
