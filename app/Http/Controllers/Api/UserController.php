<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource; // <-- Import resource
use App\Models\User; // <-- Import model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Menampilkan daftar semua user.
     */
    public function index()
    {
        // Mengambil semua user, diurutkan dari yang terbaru
        // with('roles') adalah optimasi untuk menghindari N+1 problem
        $users = User::with('roles')->latest()->get();

        // Mengembalikan data menggunakan UserResource
        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', 'string', Rule::in(['admin bidang', 'admin dinas', 'admin kecamatan', 'admin desa'])],
            'entity_id' => 'required|integer', // ID dari desa/kecamatan/bidang/dinas
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Hubungkan user ke entitas berdasarkan peran
        switch ($validated['role']) {
            case 'admin bidang':
                $user->update(['bidang_id' => $validated['entity_id']]);
                break;
            case 'admin dinas':
                $user->update(['dinas_id' => $validated['entity_id']]);
                break;
                // ... (kasus untuk desa dan kecamatan bisa ditambahkan)
        }

        $user->assignRole($validated['role']);

        return response()->json(['message' => 'User berhasil dibuat.', 'user' => $user], 201);
    }
}
