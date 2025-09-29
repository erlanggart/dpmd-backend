<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kecamatan extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function desas()
    {
        return $this->hasMany(Desa::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function musdesus()
    {
        return $this->hasManyThrough(Musdesus::class, Desa::class);
    }
}
