<?php

// dpmd-backend/app/Models/ModelsPerjadin/KegiatanBidang.php
namespace App\Models\ModelsPerjadin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KegiatanBidang extends Model
{
    use HasFactory;
    protected $table = 'kegiatan_bidang';
    protected $primaryKey = 'id_kegiatan_bidang';
    protected $fillable = ['id_kegiatan','id_bidang','personil',];
    public function kegiatan()
    {
        return $this->belongsTo(Kegiatan::class, 'id_kegiatan', 'id_kegiatan');
    }
    public function bidang()
    {
        return $this->belongsTo(Bidang::class, 'id_bidang', 'id_bidang');
    }
}