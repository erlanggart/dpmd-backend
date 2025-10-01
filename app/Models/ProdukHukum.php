<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProdukHukum extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

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

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }
}
