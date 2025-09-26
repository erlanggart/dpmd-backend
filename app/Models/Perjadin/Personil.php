<?php

// dpmd-backend/app/Models/Perjadin/Personil.php
namespace App\Models\Perjadin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personil extends Model
{
    use HasFactory;
    protected $table = 'personil';
    protected $primaryKey = 'id_personil';
    protected $fillable = ['id_bidang','nama_personil',];
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang', 'id_bidang');
    }
}