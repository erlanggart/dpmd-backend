<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Musdesus extends Model
{
    use HasFactory;

    protected $table = 'musdesus'; // Explicitly set table name

    protected $fillable = [
        'nama_file',
        'nama_file_asli',
        'path_file',
        'mime_type',
        'ukuran_file',
        'nama_pengupload',
        'email_pengupload',
        'telepon_pengupload',
        'desa_id',
        'kecamatan_id',
        'keterangan',
        'status',
        'catatan_admin',
        'tanggal_musdesus'
    ];

    protected $casts = [
        'tanggal_musdesus' => 'datetime',
        'ukuran_file' => 'integer'
    ];

    // Relationship dengan desa
    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }

    // Relationship dengan kecamatan  
    public function kecamatan()
    {
        return $this->belongsTo(Kecamatan::class);
    }
}
