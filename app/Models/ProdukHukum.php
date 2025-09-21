<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukHukum extends Model
{
    use HasFactory;

    protected $fillable = [
        'desa_id',
        'tipe_dokumen',
        'judul',
        'nomor',
        'tahun',
        'jenis',
        'singkatan_jenis',
        'tempat_penetapan',
        'tanggal_penetapan',
        'sumber',
        'subjek',
        'status_peraturan',
        'keterangan_status',
        'bahasa',
        'bidang_hukum',
        'file',
    ];

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }
}
