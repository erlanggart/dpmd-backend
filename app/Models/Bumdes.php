<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bumdes extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'desa_id',
        'kode_desa',
        'kecamatan',
        'desa',
        'namabumdesa',
        'status',
        'keterangan_tidak_aktif',
        'NIB',
        'LKPP',
        'NPWP',
        'badanhukum',
        'NamaPenasihat',
        'JenisKelaminPenasihat',
        'HPPenasihat',
        'NamaPengawas',
        'JenisKelaminPengawas',
        'HPPengawas',
        'NamaDirektur',
        'JenisKelaminDirektur',
        'HPDirektur',
        'NamaSekretaris',
        'JenisKelaminSekretaris',
        'HPSekretaris',
        'NamaBendahara',
        'JenisKelaminBendahara',
        'HPBendahara',
        'TahunPendirian',
        'AlamatBumdesa',
        'Alamatemail',
        'TotalTenagaKerja',
        'TelfonBumdes',
        'JenisUsaha',
        'JenisUsahaUtama',
        'JenisUsahaLainnya',
        'Omset2023',
        'Laba2023',
        'Omset2024',
        'Laba2024',
        'PenyertaanModal2019',
        'PenyertaanModal2020',
        'PenyertaanModal2021',
        'PenyertaanModal2022',
        'PenyertaanModal2023',
        'PenyertaanModal2024',
        'SumberLain',
        'JenisAset',
        'NilaiAset',
        'KerjasamaPihakKetiga',
        'TahunMulai-TahunBerakhir',
        'KontribusiTerhadapPADes2021',
        'KontribusiTerhadapPADes2022',
        'KontribusiTerhadapPADes2023',
        'KontribusiTerhadapPADes2024',
        'Ketapang2024',
        'Ketapang2025',
        'BantuanKementrian',
        'BantuanLaptopShopee',
        'NomorPerdes',
        'DesaWisata',
        // Foreign key fields for produk hukum integration
        'produk_hukum_perdes_id',
        'produk_hukum_sk_bumdes_id',
        // FILE FIELDS REMOVED FROM FILLABLE - harus di-handle manual di controller
        // 'LaporanKeuangan2021',
        // 'LaporanKeuangan2022',
        // 'LaporanKeuangan2023',
        // 'LaporanKeuangan2024',
        // 'Perdes',
        // 'ProfilBUMDesa',
        // 'BeritaAcara',
        // 'AnggaranDasar',
        // 'AnggaranRumahTangga',
        // 'ProgramKerja',
        // 'SK_BUM_Desa',
    ];

    /**
     * Get the desa that owns the Bumdes
     */
    public function desa()
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * Get the PERDES (Peraturan Desa) produk hukum for this BUMDES
     */
    public function produkHukumPerdes()
    {
        return $this->belongsTo(\App\Models\ProdukHukum::class, 'produk_hukum_perdes_id');
    }

    /**
     * Get the SK BUMDES produk hukum for this BUMDES
     */
    public function produkHukumSkBumdes()
    {
        return $this->belongsTo(\App\Models\ProdukHukum::class, 'produk_hukum_sk_bumdes_id');
    }
}