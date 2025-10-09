<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuidPrimary;

class Rw extends Model
{
    use HasUuidPrimary;

    protected $fillable = [
        'desa_id',
        'nomor',
        'alamat',
        'status_kelembagaan',
        'status_verifikasi',
        'produk_hukum_id'
    ];

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }

    public function rts()
    {
        return $this->hasMany(Rt::class, 'rw_id');
    }

    public function pengurus()
    {
        return $this->morphMany(Pengurus::class, 'pengurusable');
    }

    public function produkHukum()
    {
        return $this->belongsTo(ProdukHukum::class, 'produk_hukum_id');
    }
}
