<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


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
            $user = Auth::user();
            $user->load(['desa.kecamatan', 'bidang', 'dinas']);
            $token = $user->createToken('auth_token')->plainTextToken;

            // --- PERUBAHAN DI SINI ---
            // 1. Ubah objek user menjadi array
            $userData = $user->toArray();
            // 2. Tambahkan kunci 'roles' secara manual
            $userData['roles'] = $user->getRoleNames();

            return response()->json([
                'message' => 'Login berhasil',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $userData, // <-- Kirim data yang sudah dimodifikasi
            ]);
        }

        return response()->json([
            'message' => 'Email atau password salah.'
        ], 401);
    }
}
