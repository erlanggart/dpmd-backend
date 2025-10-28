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
        // Mengambil semua user dengan relasi yang dibutuhkan
        $users = User::with([
            'roles',
            'desa.kecamatan',
            'kecamatan'
        ])->latest()->get();

        // Mengembalikan data menggunakan UserResource
        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        $allowedRoles = [
            'superadmin',
            'dinas',
            'kepala_dinas',
            'sekretaris_dinas',
            // 4 Bidang
            'sarana_prasarana',
            'pemerintahan_desa',
            'kekayaan_keuangan',
            'pemberdayaan_masyarakat',
            // 3 Departemen
            'sekretariat',
            'prolap',
            'keuangan',
            'kecamatan',
            'desa'
        ];

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => ['required', 'string', Rule::in($allowedRoles)],
            'entity_id' => 'nullable|integer', // Optional untuk roles yang membutuhkan
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'], // Set role langsung di field database
        ]);

        // Hubungkan user ke entitas berdasarkan peran (hanya untuk kecamatan/desa)
        if (isset($validated['entity_id']) && $validated['entity_id']) {
            switch ($validated['role']) {
                case 'kecamatan':
                    $user->update(['kecamatan_id' => $validated['entity_id']]);
                    break;
                case 'desa':
                    $user->update(['desa_id' => $validated['entity_id']]);
                    break;
            }
        }

        // Optional: Assign Spatie role jika diperlukan
        // $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'data' => new UserResource($user)
        ], 201);
    }

    /**
     * Reset password user
     */
    public function resetPassword(Request $request, $id)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'message' => 'Password berhasil direset.',
            'data' => new UserResource($user)
        ]);
    }
}
