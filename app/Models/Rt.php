<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuidPrimary;

class Rt extends Model
{
    use HasUuidPrimary;

    protected $fillable = [
        'desa_id',
        'rw_id',
        'nomor',
        'alamat',
        'status_kelembagaan',
        'status_verifikasi'
    ];

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }

    public function rw()
    {
        return $this->belongsTo(Rw::class);
    }

    public function pengurus()
    {
        return $this->morphMany(Pengurus::class, 'pengurusable');
    }
}
