<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KegiatanSeeder extends Seeder
{
    public function run()
    {
        // Hapus data existing
        DB::table('kegiatan_bidang')->delete();
        DB::table('kegiatan')->delete();

        // Data kegiatan dummy untuk testing statistik
        $kegiatanData = [
            [
                'id_kegiatan' => 1,
                'nama_kegiatan' => 'Rapat Koordinasi Pembangunan Desa',
                'nomor_sp' => 'SP/001/2025',
                'tanggal_mulai' => '2025-09-01',
                'tanggal_selesai' => '2025-09-03',
                'lokasi' => 'Jakarta',
                'keterangan' => 'Rapat koordinasi untuk pembangunan infrastruktur desa',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_kegiatan' => 2,
                'nama_kegiatan' => 'Pelatihan Pengembangan SDM',
                'nomor_sp' => 'SP/002/2025',
                'tanggal_mulai' => '2025-09-05',
                'tanggal_selesai' => '2025-09-07',
                'lokasi' => 'Bandung',
                'keterangan' => 'Pelatihan untuk meningkatkan kualitas SDM',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_kegiatan' => 3,
                'nama_kegiatan' => 'Workshop Digitalisasi Pelayanan',
                'nomor_sp' => 'SP/003/2025',
                'tanggal_mulai' => '2025-09-10',
                'tanggal_selesai' => '2025-09-12',
                'lokasi' => 'Surabaya',
                'keterangan' => 'Workshop untuk digitalisasi layanan publik',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_kegiatan' => 4,
                'nama_kegiatan' => 'Sosialisasi Program Pemberdayaan',
                'nomor_sp' => 'SP/004/2025',
                'tanggal_mulai' => '2025-09-15',
                'tanggal_selesai' => '2025-09-17',
                'lokasi' => 'Yogyakarta',
                'keterangan' => 'Sosialisasi program pemberdayaan masyarakat',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_kegiatan' => 5,
                'nama_kegiatan' => 'Evaluasi Program Kesehatan',
                'nomor_sp' => 'SP/005/2025',
                'tanggal_mulai' => '2025-09-20',
                'tanggal_selesai' => '2025-09-22',
                'lokasi' => 'Semarang',
                'keterangan' => 'Evaluasi program kesehatan masyarakat',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_kegiatan' => 6,
                'nama_kegiatan' => 'Pelatihan Teknologi Informasi',
                'nomor_sp' => 'SP/006/2025',
                'tanggal_mulai' => '2025-08-10',
                'tanggal_selesai' => '2025-08-12',
                'lokasi' => 'Medan',
                'keterangan' => 'Pelatihan IT untuk pegawai',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_kegiatan' => 7,
                'nama_kegiatan' => 'Seminar Keuangan Desa',
                'nomor_sp' => 'SP/007/2025',
                'tanggal_mulai' => '2025-08-15',
                'tanggal_selesai' => '2025-08-17',
                'lokasi' => 'Makassar',
                'keterangan' => 'Seminar pengelolaan keuangan desa',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_kegiatan' => 8,
                'nama_kegiatan' => 'Workshop Sarana Prasarana',
                'nomor_sp' => 'SP/008/2025',
                'tanggal_mulai' => '2025-07-20',
                'tanggal_selesai' => '2025-07-22',
                'lokasi' => 'Denpasar',
                'keterangan' => 'Workshop pembangunan sarana prasarana',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('kegiatan')->insert($kegiatanData);

        // Data kegiatan_bidang dengan personil yang sesuai dengan data di tabel personil
        // Ambil ID bidang yang ada
        $bidangIds = DB::table('bidangs')->pluck('id', 'nama')->toArray();

        $kegiatanBidangData = [
            // Kegiatan 1 - Rapat Koordinasi
            [
                'id_kegiatan' => 1,
                'id_bidang' => $bidangIds['Sekretariat'] ?? 1,
                'personil' => 'Drs. HADIJANA S.Sos. M.Si, ENDANG HARI MULYADINATA S.Kom',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id_kegiatan' => 1,
                'id_bidang' => $bidangIds['Pemerintahan Desa'] ?? 2,
                'personil' => 'Nama personil dari bidang pemerintahan',
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Kegiatan 2 - Pelatihan SDM
            [
                'id_kegiatan' => 2,
                'id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'] ?? 3,
                'personil' => 'Personil pemberdayaan masyarakat',
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Kegiatan 3 - Workshop Digitalisasi
            [
                'id_kegiatan' => 3,
                'id_bidang' => $bidangIds['Sekretariat'] ?? 1,
                'personil' => 'LISNA SUSANTI S. E., TRI WIDIYARTO S. IP, M. PA',
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Kegiatan 4 - Sosialisasi
            [
                'id_kegiatan' => 4,
                'id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'] ?? 3,
                'personil' => 'Personil pemberdayaan masyarakat',
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Kegiatan 5 - Evaluasi Kesehatan
            [
                'id_kegiatan' => 5,
                'id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'] ?? 4,
                'personil' => 'Personil sarana prasarana',
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Kegiatan 6 - Pelatihan IT
            [
                'id_kegiatan' => 6,
                'id_bidang' => $bidangIds['Sekretariat'] ?? 1,
                'personil' => 'ARIS SE, FERDI SERDIANA S.E. M. Si',
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Kegiatan 7 - Seminar Keuangan
            [
                'id_kegiatan' => 7,
                'id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'] ?? 5,
                'personil' => 'Personil keuangan desa',
                'created_at' => now(),
                'updated_at' => now()
            ],
            
            // Kegiatan 8 - Workshop Sarana Prasarana
            [
                'id_kegiatan' => 8,
                'id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'] ?? 4,
                'personil' => 'Personil sarana prasarana',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('kegiatan_bidang')->insert($kegiatanBidangData);
    }
}
