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
        $mingguan = Kegiatan::whereBetween('tanggal_mulai', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $bulanan = Kegiatan::whereBetween('tanggal_mulai', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])->count();
        $per_bidang = KegiatanBidang::select('bidangs.id as id_bidang', 'bidangs.nama as nama_bidang', DB::raw('count(*) as total'))
            ->join('bidangs', 'kegiatan_bidang.id_bidang', '=', 'bidangs.id')
            ->groupBy('bidangs.id', 'bidangs.nama')
            ->get();
        return response()->json(compact('mingguan', 'bulanan', 'per_bidang'));
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
        
        return response()->json(array_values($weekly_schedule));
    }
}