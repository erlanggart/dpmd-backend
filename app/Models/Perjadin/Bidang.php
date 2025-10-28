<?php

// dpmd-backend/app/Models/Perjadin/Bidang.php
namespace App\Models\Perjadin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    use HasFactory;
    protected $table = 'bidang';
    protected $primaryKey = 'id_bidang';
    protected $fillable = ['nama_bidang',];
    public function personil()
    {
        return $this->hasMany(Personil::class, 'id_bidang', 'id_bidang');
    }
}