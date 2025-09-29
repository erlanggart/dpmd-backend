<?php

// dpmd-backend/app/Models/Kegiatan.php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kegiatan extends Model
{
    use HasFactory;
    protected $table = 'kegiatan';
    protected $primaryKey = 'id_kegiatan';
    public $incrementing = true;
    protected $fillable = ['nama_kegiatan','nomor_sp','tanggal_mulai','tanggal_selesai','lokasi','keterangan',];
    public function details()
    {
        return $this->hasMany(KegiatanBidang::class, 'id_kegiatan', 'id_kegiatan');
    }
}