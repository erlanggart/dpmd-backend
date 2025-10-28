<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ModuleSetting;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleEnabled
{
    public function handle(Request $request, Closure $next, string $moduleName): Response
    {
        $user = $request->user();

        // Superadmin bypass - full access
        if ($user && $user->role === 'superadmin') {
            return $next($request);
        }

        // Admin bidang bypass - full access
        $adminRoles = ['pemberdayaan_masyarakat', 'pemerintahan_desa', 'sekretariat', 'sarana_prasarana', 'kekayaan_keuangan'];
        if ($user && in_array($user->role, $adminRoles)) {
            return $next($request);
        }

        // User desa - check module status
        if ($user && $user->role === 'desa') {
            $isEnabled = ModuleSetting::isModuleEnabled($moduleName);
            
            if (!$isEnabled) {
                // Allow GET requests (view/read data)
                if ($request->isMethod('GET')) {
                    return $next($request);
                }
                
                // Block POST, PUT, PATCH, DELETE requests (write operations)
                return response()->json([
                    'success' => false,
                    'message' => 'Modul ini sedang dinonaktifkan. Anda hanya dapat melihat data, tidak dapat menambah atau mengedit.',
                    'module' => $moduleName
                ], 403);
            }
        }

        return $next($request);
    }
}
