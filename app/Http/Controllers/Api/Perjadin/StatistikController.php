<?php

namespace App\Http\Controllers\Api\Perjadin;

use App\Http\Controllers\Controller;
use App\Models\Perjadin\Kegiatan;
use App\Models\Perjadin\KegiatanBidang;
use App\Models\Bidang;
use App\Models\Perjadin\Personil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatistikController extends Controller
{
    public function getStatistikPerjadin(Request $request)
    {
        $period = $request->get('period', 'minggu'); // minggu, bulan, tahun
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));

        try {
            // Total perjalanan dinas menggunakan model yang sama dengan Dashboard
            $totalPerjalanan = Kegiatan::whereYear('tanggal_mulai', $year)->count();

            // Total bidang yang terlibat dalam perjalanan dinas
            $totalBidang = KegiatanBidang::join('kegiatan', 'kegiatan_bidang.id_kegiatan', '=', 'kegiatan.id_kegiatan')
                ->whereYear('kegiatan.tanggal_mulai', $year)
                ->distinct('kegiatan_bidang.id_bidang')
                ->count('kegiatan_bidang.id_bidang');
            
            // Total personil yang terlibat berdasarkan data aktual di field personil
            $kegiatanBidangData = KegiatanBidang::join('kegiatan', 'kegiatan_bidang.id_kegiatan', '=', 'kegiatan.id_kegiatan')
                ->whereYear('kegiatan.tanggal_mulai', $year)
                ->whereNotNull('kegiatan_bidang.personil')
                ->where('kegiatan_bidang.personil', '!=', '')
                ->pluck('kegiatan_bidang.personil');

            // Hitung unique personil dari semua kegiatan
            $uniquePersonil = [];
            foreach ($kegiatanBidangData as $personilString) {
                // Parse personil string (comma-separated atau JSON)
                $personilList = [];
                
                // Coba decode JSON dulu
                $jsonDecoded = json_decode($personilString, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
                    // Jika JSON valid, ambil nama dari setiap personil
                    foreach ($jsonDecoded as $person) {
                        if (is_array($person) && isset($person['nama'])) {
                            $personilList[] = trim(strtolower($person['nama']));
                        } elseif (is_string($person)) {
                            $personilList[] = trim(strtolower($person));
                        }
                    }
                } else {
                    // Jika bukan JSON, split by comma
                    $personilList = array_map(function($name) {
                        return trim(strtolower($name));
                    }, array_filter(explode(',', $personilString)));
                }
                
                // Tambahkan ke unique list
                foreach ($personilList as $personil) {
                    if (!empty($personil)) {
                        $uniquePersonil[$personil] = true;
                    }
                }
            }
            
            $totalPersonil = count($uniquePersonil);

            // Generate grafik data berdasarkan periode
            $grafikData = $this->generateGrafikData($period, $year, $month);

            // Top 5 bidang dengan perjalanan terbanyak - menggunakan logic yang sama dengan Dashboard
            $topBidang = KegiatanBidang::select('bidangs.nama', DB::raw('COUNT(DISTINCT kegiatan.id_kegiatan) as jumlah'))
                ->join('bidangs', 'kegiatan_bidang.id_bidang', '=', 'bidangs.id')
                ->join('kegiatan', 'kegiatan_bidang.id_kegiatan', '=', 'kegiatan.id_kegiatan')
                ->whereYear('kegiatan.tanggal_mulai', $year)
                ->groupBy('bidangs.id', 'bidangs.nama')
                ->orderBy('jumlah', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($item) use ($totalPerjalanan) {
                    $item->persentase = $totalPerjalanan > 0 ? round(($item->jumlah / $totalPerjalanan) * 100, 1) : 0;
                    return $item;
                });

            // Data personil terlibat per bidang berdasarkan data aktual
            $bidangPersonilData = KegiatanBidang::select('bidangs.nama as bidang', 'kegiatan_bidang.personil')
                ->join('bidangs', 'kegiatan_bidang.id_bidang', '=', 'bidangs.id')
                ->join('kegiatan', 'kegiatan_bidang.id_kegiatan', '=', 'kegiatan.id_kegiatan')
                ->whereYear('kegiatan.tanggal_mulai', $year)
                ->whereNotNull('kegiatan_bidang.personil')
                ->where('kegiatan_bidang.personil', '!=', '')
                ->get()
                ->groupBy('bidang');

            $personilPerBidang = [];
            foreach ($bidangPersonilData as $bidangNama => $records) {
                $uniquePersonilBidang = [];
                
                foreach ($records as $record) {
                    $personilString = $record->personil;
                    $personilList = [];
                    
                    // Parse personil string (sama seperti sebelumnya)
                    $jsonDecoded = json_decode($personilString, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
                        foreach ($jsonDecoded as $person) {
                            if (is_array($person) && isset($person['nama'])) {
                                $personilList[] = trim(strtolower($person['nama']));
                            } elseif (is_string($person)) {
                                $personilList[] = trim(strtolower($person));
                            }
                        }
                    } else {
                        $personilList = array_map(function($name) {
                            return trim(strtolower($name));
                        }, array_filter(explode(',', $personilString)));
                    }
                    
                    foreach ($personilList as $personil) {
                        if (!empty($personil)) {
                            $uniquePersonilBidang[$personil] = true;
                        }
                    }
                }
                
                $personilPerBidang[] = (object)[
                    'bidang' => $bidangNama,
                    'jumlah_personil' => count($uniquePersonilBidang)
                ];
            }
            
            // Sort by jumlah_personil descending and take top 5
            usort($personilPerBidang, function($a, $b) {
                return $b->jumlah_personil - $a->jumlah_personil;
            });
            $personilPerBidang = array_slice($personilPerBidang, 0, 5);

            return response()->json([
                'success' => true,
                'data' => [
                    'totalPerjalanan' => $totalPerjalanan,
                    'totalBidang' => $totalBidang,
                    'totalPersonil' => $totalPersonil,
                    'grafikData' => $grafikData,
                    'topBidang' => $topBidang,
                    'personilPerBidang' => $personilPerBidang,
                    'trendData' => [] // Untuk keperluan masa depan
                ]
            ]);

        } catch (\Exception $e) {
            // Kembalikan error untuk debugging
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data statistik',
                'error' => $e->getMessage(),
                'data' => [
                    'totalPerjalanan' => 0,
                    'totalBidang' => 0,
                    'totalPersonil' => 0,
                    'grafikData' => [],
                    'topBidang' => [],
                    'personilPerBidang' => [],
                    'trendData' => []
                ]
            ], 500);
        }
    }

    private function generateGrafikData($period, $year, $month)
    {
        $grafikData = [];

        try {
            if ($period === 'minggu') {
                // Data per minggu dalam bulan
                $startDate = Carbon::createFromDate($year, $month, 1);
                $endDate = $startDate->copy()->endOfMonth();
                
                for ($week = 1; $week <= 4; $week++) {
                    $weekStart = $startDate->copy()->addWeeks($week - 1);
                    $weekEnd = $weekStart->copy()->addDays(6);
                    
                    if ($weekEnd->gt($endDate)) {
                        $weekEnd = $endDate;
                    }
                    
                    $count = Kegiatan::whereBetween('tanggal_mulai', [$weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')])
                        ->count();
                    
                    $grafikData[] = [
                        'label' => "Minggu $week",
                        'value' => $count
                    ];
                }
            } elseif ($period === 'bulan') {
                // Data per bulan dalam tahun
                for ($m = 1; $m <= 12; $m++) {
                    $count = Kegiatan::whereYear('tanggal_mulai', $year)
                        ->whereMonth('tanggal_mulai', $m)
                        ->count();
                    
                    $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
                    
                    $grafikData[] = [
                        'label' => $monthNames[$m - 1],
                        'value' => $count
                    ];
                }
            } else {
                // Data per tahun
                for ($y = $year - 4; $y <= $year; $y++) {
                    $count = Kegiatan::whereYear('tanggal_mulai', $y)->count();
                    
                    $grafikData[] = [
                        'label' => (string)$y,
                        'value' => $count
                    ];
                }
            }
        } catch (\Exception $e) {
            // Return empty data jika error
            return [];
        }

        return $grafikData;
    }

    private function getDummyStatistikData($period, $year, $month)
    {
        // Dummy data untuk demo ketika tabel belum ada
        $dummyData = [
            'totalPerjalanan' => 156,
            'totalBidang' => 8,
            'totalPersonil' => 89,
            'grafikData' => [],
            'topBidang' => [
                ['nama' => 'Sekretariat', 'jumlah' => 45, 'persentase' => 28.8],
                ['nama' => 'Pemberdayaan Masyarakat Desa', 'jumlah' => 32, 'persentase' => 20.5],
                ['nama' => 'Pemerintahan Desa', 'jumlah' => 28, 'persentase' => 17.9],
                ['nama' => 'Keuangan Desa', 'jumlah' => 24, 'persentase' => 15.4],
                ['nama' => 'Sarana Prasarana', 'jumlah' => 18, 'persentase' => 11.5]
            ],
            'trendData' => []
        ];

        // Generate dummy grafik data
        if ($period === 'minggu') {
            $dummyData['grafikData'] = [
                ['label' => 'Minggu 1', 'value' => 12],
                ['label' => 'Minggu 2', 'value' => 18],
                ['label' => 'Minggu 3', 'value' => 15],
                ['label' => 'Minggu 4', 'value' => 22]
            ];
        } elseif ($period === 'bulan') {
            $dummyData['grafikData'] = [
                ['label' => 'Jan', 'value' => 35], ['label' => 'Feb', 'value' => 28],
                ['label' => 'Mar', 'value' => 42], ['label' => 'Apr', 'value' => 38],
                ['label' => 'Mei', 'value' => 31], ['label' => 'Jun', 'value' => 45],
                ['label' => 'Jul', 'value' => 52], ['label' => 'Ags', 'value' => 48],
                ['label' => 'Sep', 'value' => 41], ['label' => 'Okt', 'value' => 36],
                ['label' => 'Nov', 'value' => 29], ['label' => 'Des', 'value' => 33]
            ];
        } else {
            $dummyData['grafikData'] = [
                ['label' => '2021', 'value' => 320],
                ['label' => '2022', 'value' => 385],
                ['label' => '2023', 'value' => 452],
                ['label' => '2024', 'value' => 398],
                ['label' => '2025', 'value' => 467]
            ];
        }

        return response()->json($dummyData);
    }
}
