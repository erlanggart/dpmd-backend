<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasUuidPrimary;

class Posyandu extends Model
{
    use HasUuidPrimary;

    protected $fillable = ['desa_id', 'nama', 'alamat', 'status_kelembagaan', 'status_verifikasi'];

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }

    public function pengurus()
    {
        return $this->morphMany(Pengurus::class, 'pengurusable');
    }
}
