<?php

// dpmd-backend/app/Http/Controllers/Api/Perjadin/DashboardController.php
namespace App\Http\Controllers\Api\Perjadin;

use App\Http\Controllers\Controller;
use App\Models\Perjadin\Kegiatan;
use App\Models\Perjadin\KegiatanBidang;
use App\Models\Bidang;
use App\Models\Perjadin\Personil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            $total = Kegiatan::count();
            $mingguan = Kegiatan::whereBetween('tanggal_mulai', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
            $bulanan = Kegiatan::whereBetween('tanggal_mulai', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count();
            
            // Hanya ambil bidang yang memiliki kegiatan (menghindari "bidang kerja . 0 kegiatan")
            $per_bidang = KegiatanBidang::select('bidangs.id as id_bidang', 'bidangs.nama as nama_bidang', DB::raw('count(DISTINCT kegiatan_bidang.id_kegiatan) as total'))
                ->join('bidangs', 'kegiatan_bidang.id_bidang', '=', 'bidangs.id')
                ->join('kegiatan', 'kegiatan_bidang.id_kegiatan', '=', 'kegiatan.id_kegiatan') // Join dengan kegiatan untuk memastikan kegiatan exists
                ->groupBy('bidangs.id', 'bidangs.nama')
                ->having('total', '>', 0) // Hanya tampilkan bidang yang memiliki kegiatan
                ->orderBy('total', 'desc')
                ->get();

            // Hitung total personil yang terlibat berdasarkan data personil di kegiatan_bidang
            $totalPersonilTerlibat = KegiatanBidang::whereNotNull('personil')
                ->where('personil', '!=', '')
                ->get()
                ->sum(function($detail) {
                    // Hitung jumlah personil dari string yang dipisah koma
                    $personilList = array_filter(array_map('trim', explode(',', $detail->personil ?? '')));
                    return count($personilList);
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'mingguan' => $mingguan,
                    'bulanan' => $bulanan,
                    'total_personil_terlibat' => $totalPersonilTerlibat,
                    'per_bidang' => $per_bidang
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function weeklySchedule()
    {
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY); // Mengubah endOfWeek ke Minggu
        
        $hari_indonesia = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $weekly_schedule = [];
        
        // Loop 7 hari dari Senin hingga Minggu
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $day_name = $hari_indonesia[$date->dayOfWeek]; // Dapatkan nama hari
            $weekly_schedule[$date->format('Y-m-d')] = [
                'tanggal' => $date->format('Y-m-d'),
                'hari' => $day_name,
                'kegiatan' => []
            ];
        }

        // Ambil semua kegiatan yang beririsan dengan rentang minggu saat ini
        $kegiatan = Kegiatan::with(['details.bidang'])
            ->where(function ($query) use ($startOfWeek, $endOfWeek) {
                $query->where('tanggal_mulai', '<=', $endOfWeek)
                      ->where('tanggal_selesai', '>=', $startOfWeek);
            })
            ->orderBy('tanggal_mulai', 'asc')
            ->get();
        
        // Distribusikan kegiatan ke setiap hari dalam rentang tanggalnya
        foreach ($kegiatan as $keg) {
            $currentDate = Carbon::parse($keg->tanggal_mulai);
            $endDate = Carbon::parse($keg->tanggal_selesai);

            while ($currentDate->lte($endDate)) {
                $formattedDate = $currentDate->format('Y-m-d');
                if (isset($weekly_schedule[$formattedDate])) {
                    $weekly_schedule[$formattedDate]['kegiatan'][] = $keg;
                }
                $currentDate->addDay();
            }
        }
        
        return response()->json([
            'success' => true,
            'data' => array_values($weekly_schedule)
        ]);
    }
}