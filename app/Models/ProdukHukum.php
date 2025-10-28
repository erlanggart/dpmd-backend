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

    // Relasi untuk SK Pembentukan Lembaga
    public function rws()
    {
        return $this->hasMany(Rw::class, 'produk_hukum_id');
    }

    public function rts()
    {
        return $this->hasMany(Rt::class, 'produk_hukum_id');
    }

    public function posyandus()
    {
        return $this->hasMany(Posyandu::class, 'produk_hukum_id');
    }

    public function karangTarunas()
    {
        return $this->hasMany(KarangTaruna::class, 'produk_hukum_id');
    }

    public function lpms()
    {
        return $this->hasMany(Lpm::class, 'produk_hukum_id');
    }

    public function pkks()
    {
        return $this->hasMany(Pkk::class, 'produk_hukum_id');
    }

    public function satlinmas()
    {
        return $this->hasMany(Satlinmas::class, 'produk_hukum_id');
    }

    // Relasi untuk SK Pengangkatan Pengurus
    public function pengurus()
    {
        return $this->hasMany(Pengurus::class, 'produk_hukum_id');
    }
}
