<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Desa extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function aparatur()
    {
        return $this->hasMany(AparaturDesa::class);
    }

    public function profil()
    {
        return $this->hasOne(ProfilDesa::class);
    }
}
