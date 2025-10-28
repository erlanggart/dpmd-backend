<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role, // Gunakan field role dari database
            'created_at' => $this->created_at->format('d M Y'),
            // Tetap sediakan roles dari Spatie jika diperlukan
            'spatie_roles' => $this->getRoleNames(),
            // Tambahkan data desa jika user memiliki desa_id
            'desa' => $this->when($this->desa_id, function () {
                return [
                    'id' => $this->desa->id,
                    'nama' => $this->desa->nama,
                    'status_pemerintahan' => $this->desa->status_pemerintahan,
                    'kecamatan' => [
                        'id' => $this->desa->kecamatan->id,
                        'nama' => $this->desa->kecamatan->nama,
                    ]
                ];
            }),
            // Tambahkan data kecamatan jika user memiliki kecamatan_id
            'kecamatan' => $this->when($this->kecamatan_id, function () {
                return [
                    'id' => $this->kecamatan->id,
                    'nama' => $this->kecamatan->nama,
                ];
            }),
        ];
    }
}
