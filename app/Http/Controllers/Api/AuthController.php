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
        // 1. Validasi input
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Coba otentikasi user
        if (Auth::attempt($credentials)) {
            // 3. Jika berhasil, dapatkan user
            $user = Auth::user();

            // 4. Buat token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            // 5. Kembalikan data user dan token
            return response()->json([
                'message' => 'Login berhasil',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames() // Mengambil nama peran dari Spatie
                ]
            ]);
        }

        // 6. Jika gagal, kembalikan error
        return response()->json([
            'message' => 'Email atau password salah.'
        ], 401);
    }
}
