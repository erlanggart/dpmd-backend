<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuidPrimary;

class Pengurus extends Model
{
    use HasUuidPrimary;

    protected $table = 'pengurus';

    protected $fillable = [
        'desa_id',
        'pengurusable_id',
        'pengurusable_type',
        'jabatan',
        'tanggal_mulai_jabatan',
        'tanggal_akhir_jabatan',
        'status_jabatan',
        'status_verifikasi',
        'produk_hukum_id',
        'nama_lengkap',
        'nik',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'status_perkawinan',
        'alamat',
        'no_telepon',
        'pendidikan',
        'avatar'
    ];

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }

    public function pengurusable()
    {
        return $this->morphTo();
    }

    public function produkHukum()
    {
        return $this->belongsTo(ProdukHukum::class, 'produk_hukum_id');
    }
}
