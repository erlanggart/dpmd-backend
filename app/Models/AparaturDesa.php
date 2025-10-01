<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AparaturDesa extends Model
{
    use HasFactory;

    protected $table = 'aparatur_desa';

    // UUID primary key
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'desa_id',
        'nama_lengkap',
        'jabatan',
        'nipd',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'pendidikan_terakhir',
        'agama',
        'pangkat_golongan',
        'tanggal_pengangkatan',
        'nomor_sk_pengangkatan',
        'tanggal_pemberhentian',
        'nomor_sk_pemberhentian',
        'keterangan',
        'status',
        'produk_hukum_id',
        'bpjs_kesehatan_nomor',
        'bpjs_ketenagakerjaan_nomor',
        'file_bpjs_kesehatan',
        'file_bpjs_ketenagakerjaan',
        'file_pas_foto',
        'file_ktp',
        'file_kk',
        'file_akta_kelahiran',
        'file_ijazah_terakhir',
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

    /**
     * Get the desa that owns the aparatur.
     */
    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * Get the produk hukum associated with the aparatur.
     */
    public function produkHukum()
    {
        return $this->belongsTo(ProdukHukum::class);
    }
}
