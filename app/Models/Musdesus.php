<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

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
        'petugas_id',
        'keterangan',
        'status',
        'catatan_admin',
        'tanggal_musdesus'
    ];

    protected $casts = [
        'tanggal_musdesus' => 'datetime',
        'ukuran_file' => 'integer'
    ];

    protected $appends = [
        'file_url'
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

    /**
     * Get the URL for the file
     */
    public function getFileUrlAttribute()
    {
        if (!$this->nama_file) {
            return null;
        }

        // Generate URL berdasarkan environment
        if (app()->environment('production')) {
            return config('app.url') . '/api/uploads/musdesus/' . $this->nama_file;
        } else {
            return config('app.url') . '/storage/musdesus/' . $this->nama_file;
        }
    }
}
