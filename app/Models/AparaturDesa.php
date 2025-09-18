<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AparaturDesa extends Model
{
    protected $guarded = []; // Agar semua field bisa diisi massal

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }
}
