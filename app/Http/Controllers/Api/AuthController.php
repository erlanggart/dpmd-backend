<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Load relationships jika ada
            try {
                $user->load(['desa.kecamatan', 'bidang', 'dinas']);
            } catch (\Exception $e) {
                // Ignore jika relationship tidak ada
            }
            
            $token = $user->createToken('auth_token')->plainTextToken;

            // Konversi user ke array
            $userData = $user->toArray();
            
            // Tentukan roles berdasarkan jenis user
            $roles = [];
            $bidangRoles = ['sekretariat', 'sarana_prasarana', 'kekayaan_keuangan', 'pemberdayaan_masyarakat', 'pemerintahan_desa'];
            
            if ($user->role === 'superadmin') {
                $roles = ['superadmin'];
                // Superadmin tidak memiliki bidangRole
            } else if (in_array($user->role, $bidangRoles)) {
                $roles = [$user->role]; // Gunakan role bidang spesifik langsung
                $userData['bidangRole'] = $user->role; // Simpan role bidang spesifik
            } else if ($user->role === 'desa') {
                $roles = ['desa'];
            } else if ($user->role === 'kecamatan') {
                $roles = ['kecamatan'];
            } else if ($user->role === 'dinas') {
                $roles = ['dinas'];
            } else {
                $roles = [$user->role ?? 'user'];
            }
            
            $userData['roles'] = $roles;

            return response()->json([
                'message' => 'Login berhasil',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $userData,
            ]);
        }

        return response()->json([
            'message' => 'Email atau password salah.'
        ], 401);
    }

    /**
     * Handle authentication for bidang users.
     */
    public function loginBidang(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            
            // Check if user has a valid bidang role
            $bidangRoles = ['sekretariat', 'sarana_prasarana', 'kekayaan_keuangan', 'pemberdayaan_masyarakat', 'pemerintahan_desa'];
            
            if (!in_array($user->role, $bidangRoles)) {
                return response()->json([
                    'message' => 'Akses ditolak. Anda tidak memiliki hak akses ke dashboard bidang.'
                ], 403);
            }

            $token = $user->createToken('bidang_auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login bidang berhasil',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Email atau password salah.'
        ], 401);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Get the authenticated User.
     */
    public function user(Request $request)
    {
        $user = $request->user();
        
        // Load relationships jika ada
        if (method_exists($user, 'load')) {
            $user->load(['desa.kecamatan', 'bidang', 'dinas']);
        }
        
        return response()->json($user);
    }
}
