<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Rw;
use App\Models\Rt;
use App\Models\Posyandu;
use App\Models\KarangTaruna;
use App\Models\Lpm;
use App\Models\Satlinmas;
use App\Models\Pkk;
use App\Models\Pengurus;
use Illuminate\Http\Request;

class KelembagaanController extends Controller
{
    /**
     * Get comprehensive kelembagaan data for all kecamatan
     */
    public function index()
    {
        try {
            $kecamatans = Kecamatan::with([
                'desas' => function ($query) {
                    $query->select('id', 'nama', 'kecamatan_id', 'status_pemerintahan');
                }
            ])->get();

            $kelembagaanData = $kecamatans->map(function ($kecamatan) {
                $desaIds = $kecamatan->desas->pluck('id');

                // Get all kelembagaan data for this kecamatan's desas (hanya yang aktif)
                $rws = Rw::whereIn('desa_id', $desaIds)
                    ->where('status_kelembagaan', 'aktif')
                    ->with([
                        'rts' => function ($query) {
                            $query->where('status_kelembagaan', 'aktif');
                        },
                        'pengurus' => function ($query) {
                            $query->where('status_jabatan', 'aktif');
                        }
                    ])->get();
                $posyandus = Posyandu::whereIn('desa_id', $desaIds)
                    ->where('status_kelembagaan', 'aktif')
                    ->with(['pengurus' => function ($query) {
                        $query->where('status_jabatan', 'aktif');
                    }])->get();
                $karangTarunas = KarangTaruna::whereIn('desa_id', $desaIds)
                    ->where('status_kelembagaan', 'aktif')
                    ->with(['pengurus' => function ($query) {
                        $query->where('status_jabatan', 'aktif');
                    }])->get();
                $lpms = Lpm::whereIn('desa_id', $desaIds)
                    ->where('status_kelembagaan', 'aktif')
                    ->with(['pengurus' => function ($query) {
                        $query->where('status_jabatan', 'aktif');
                    }])->get();
                $satlinmas = Satlinmas::whereIn('desa_id', $desaIds)
                    ->where('status_kelembagaan', 'aktif')
                    ->with(['pengurus' => function ($query) {
                        $query->where('status_jabatan', 'aktif');
                    }])->get();
                $pkks = Pkk::whereIn('desa_id', $desaIds)
                    ->where('status_kelembagaan', 'aktif')
                    ->with(['pengurus' => function ($query) {
                        $query->where('status_jabatan', 'aktif');
                    }])->get();

                // Process each desa
                $desasWithKelembagaan = $kecamatan->desas->map(function ($desa) use ($rws, $posyandus, $karangTarunas, $lpms, $satlinmas, $pkks) {
                    $desaRws = $rws->where('desa_id', $desa->id);
                    $desaPosyandus = $posyandus->where('desa_id', $desa->id);
                    $desaKarangTaruna = $karangTarunas->where('desa_id', $desa->id)->first();
                    $desaLpm = $lpms->where('desa_id', $desa->id)->first();
                    $desaSatlinmas = $satlinmas->where('desa_id', $desa->id)->first();
                    $desaPkk = $pkks->where('desa_id', $desa->id)->first();

                    // Calculate total RTs and their pengurus
                    $totalRts = $desaRws->sum(function ($rw) {
                        return $rw->rts->count();
                    });

                    // Count pengurus for each kelembagaan
                    $rwPengurus = $desaRws->sum(function ($rw) {
                        return $rw->pengurus->count();
                    });

                    $rtPengurus = $desaRws->sum(function ($rw) {
                        return $rw->rts->sum(function ($rt) {
                            return $rt->pengurus->count();
                        });
                    });

                    $posyanduPengurus = $desaPosyandus->sum(function ($posyandu) {
                        return $posyandu->pengurus->count();
                    });

                    return [
                        'id' => $desa->id,
                        'nama' => $desa->nama,
                        'status_pemerintahan' => $desa->status_pemerintahan,
                        'kelembagaan' => [
                            'rw' => $desaRws->count(),
                            'rt' => $totalRts,
                            'posyandu' => $desaPosyandus->count(),
                            'karangTaruna' => $desaKarangTaruna ? 'Terbentuk' : 'Belum Terbentuk',
                            'lpm' => $desaLpm ? 'Terbentuk' : 'Belum Terbentuk',
                            'satlinmas' => $desaSatlinmas ? 'Terbentuk' : 'Belum Terbentuk',
                            'pkk' => $desaPkk ? 'Terbentuk' : 'Belum Terbentuk',
                        ],
                        'pengurus' => [
                            'rw' => $rwPengurus,
                            'rt' => $rtPengurus,
                            'posyandu' => $posyanduPengurus,
                            'karangTaruna' => $desaKarangTaruna ? $desaKarangTaruna->pengurus->count() : 0,
                            'lpm' => $desaLpm ? $desaLpm->pengurus->count() : 0,
                            'satlinmas' => $desaSatlinmas ? $desaSatlinmas->pengurus->count() : 0,
                            'pkk' => $desaPkk ? $desaPkk->pengurus->count() : 0,
                        ]
                    ];
                });

                // Calculate totals for kecamatan
                $totalKelembagaan = $desasWithKelembagaan->reduce(function ($carry, $desa) {
                    return [
                        'rw' => $carry['rw'] + $desa['kelembagaan']['rw'],
                        'rt' => $carry['rt'] + $desa['kelembagaan']['rt'],
                        'posyandu' => $carry['posyandu'] + $desa['kelembagaan']['posyandu'],
                        'karangTaruna' => $carry['karangTaruna'] + ($desa['kelembagaan']['karangTaruna'] === 'Terbentuk' ? 1 : 0),
                        'lpm' => $carry['lpm'] + ($desa['kelembagaan']['lpm'] === 'Terbentuk' ? 1 : 0),
                        'satlinmas' => $carry['satlinmas'] + ($desa['kelembagaan']['satlinmas'] === 'Terbentuk' ? 1 : 0),
                        'pkk' => $carry['pkk'] + ($desa['kelembagaan']['pkk'] === 'Terbentuk' ? 1 : 0),
                    ];
                }, ['rw' => 0, 'rt' => 0, 'posyandu' => 0, 'karangTaruna' => 0, 'lpm' => 0, 'satlinmas' => 0, 'pkk' => 0]);

                $totalPengurus = $desasWithKelembagaan->reduce(function ($carry, $desa) {
                    return [
                        'rw' => $carry['rw'] + $desa['pengurus']['rw'],
                        'rt' => $carry['rt'] + $desa['pengurus']['rt'],
                        'posyandu' => $carry['posyandu'] + $desa['pengurus']['posyandu'],
                        'karangTaruna' => $carry['karangTaruna'] + $desa['pengurus']['karangTaruna'],
                        'lpm' => $carry['lpm'] + $desa['pengurus']['lpm'],
                        'satlinmas' => $carry['satlinmas'] + $desa['pengurus']['satlinmas'],
                        'pkk' => $carry['pkk'] + $desa['pengurus']['pkk'],
                    ];
                }, ['rw' => 0, 'rt' => 0, 'posyandu' => 0, 'karangTaruna' => 0, 'lpm' => 0, 'satlinmas' => 0, 'pkk' => 0]);

                return [
                    'id' => $kecamatan->id,
                    'nama' => $kecamatan->nama,
                    'desas' => $desasWithKelembagaan,
                    'totalKelembagaan' => $totalKelembagaan,
                    'totalPengurus' => $totalPengurus,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data kelembagaan berhasil diambil',
                'data' => $kelembagaanData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kelembagaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get kelembagaan summary statistics
     */
    public function summary()
    {
        try {
            // Count kecamatan and desa/kelurahan
            $totalKecamatan = Kecamatan::count();
            $totalDesa = Desa::where('status_pemerintahan', 'desa')->count();
            $totalKelurahan = Desa::where('status_pemerintahan', 'kelurahan')->count();
            $totalDesaKelurahan = $totalDesa + $totalKelurahan;

            // Count kelembagaan (hanya yang aktif)
            $totalRw = Rw::where('status_kelembagaan', 'aktif')->count();
            $totalRt = Rt::where('status_kelembagaan', 'aktif')->count();
            $totalPosyandu = Posyandu::where('status_kelembagaan', 'aktif')->count();
            $totalKarangTaruna = KarangTaruna::where('status_kelembagaan', 'aktif')->count();
            $totalLpm = Lpm::where('status_kelembagaan', 'aktif')->count();
            $totalSatlinmas = Satlinmas::where('status_kelembagaan', 'aktif')->count();
            $totalPkk = Pkk::where('status_kelembagaan', 'aktif')->count();

            // Count total pengurus (hanya yang aktif dari kelembagaan aktif)
            $totalPengurusRw = Pengurus::where('pengurusable_type', 'App\Models\Rw')
                ->where('status_jabatan', 'aktif')
                ->whereHas('pengurusable', function ($query) {
                    $query->where('status_kelembagaan', 'aktif');
                })->count();
            $totalPengurusRt = Pengurus::where('pengurusable_type', 'App\Models\Rt')
                ->where('status_jabatan', 'aktif')
                ->whereHas('pengurusable', function ($query) {
                    $query->where('status_kelembagaan', 'aktif');
                })->count();
            $totalPengurusPosyandu = Pengurus::where('pengurusable_type', 'App\Models\Posyandu')
                ->where('status_jabatan', 'aktif')
                ->whereHas('pengurusable', function ($query) {
                    $query->where('status_kelembagaan', 'aktif');
                })->count();
            $totalPengurusKarangTaruna = Pengurus::where('pengurusable_type', 'App\Models\KarangTaruna')
                ->where('status_jabatan', 'aktif')
                ->whereHas('pengurusable', function ($query) {
                    $query->where('status_kelembagaan', 'aktif');
                })->count();
            $totalPengurusLpm = Pengurus::where('pengurusable_type', 'App\Models\Lpm')
                ->where('status_jabatan', 'aktif')
                ->whereHas('pengurusable', function ($query) {
                    $query->where('status_kelembagaan', 'aktif');
                })->count();
            $totalPengurusSatlinmas = Pengurus::where('pengurusable_type', 'App\Models\Satlinmas')
                ->where('status_jabatan', 'aktif')
                ->whereHas('pengurusable', function ($query) {
                    $query->where('status_kelembagaan', 'aktif');
                })->count();
            $totalPengurusPkk = Pengurus::where('pengurusable_type', 'App\Models\Pkk')
                ->where('status_jabatan', 'aktif')
                ->whereHas('pengurusable', function ($query) {
                    $query->where('status_kelembagaan', 'aktif');
                })->count();

            // Hitung berapa desa/kelurahan yang sudah memiliki kelembagaan aktif
            $desaWithKarangTaruna = KarangTaruna::where('status_kelembagaan', 'aktif')->distinct('desa_id')->count();
            $desaWithLpm = Lpm::where('status_kelembagaan', 'aktif')->distinct('desa_id')->count();
            $desaWithSatlinmas = Satlinmas::where('status_kelembagaan', 'aktif')->distinct('desa_id')->count();

            // Breakdown by desa/kelurahan status
            $desaIds = Desa::where('status_pemerintahan', 'desa')->pluck('id');
            $kelurahanIds = Desa::where('status_pemerintahan', 'kelurahan')->pluck('id');

            // Stats untuk desa (hanya yang aktif)
            $desaStats = [
                'count' => $totalDesa,
                'rw' => Rw::whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif')->count(),
                'rt' => Rt::whereHas('rw', function ($query) use ($desaIds) {
                    $query->whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif');
                })->where('status_kelembagaan', 'aktif')->count(),
                'posyandu' => Posyandu::whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif')->count(),
                'karangTaruna' => KarangTaruna::whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif')->count(),
                'lpm' => Lpm::whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif')->count(),
                'satlinmas' => Satlinmas::whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif')->count(),
                'pengurus' => [
                    'rw' => Pengurus::where('pengurusable_type', 'App\Models\Rw')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($desaIds) {
                            $query->whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                    'rt' => Pengurus::where('pengurusable_type', 'App\Models\Rt')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($desaIds) {
                            $query->where('status_kelembagaan', 'aktif')
                                ->whereIn('desa_id', $desaIds);
                        })->count(),
                    'posyandu' => Pengurus::where('pengurusable_type', 'App\Models\Posyandu')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($desaIds) {
                            $query->whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                    'karangTaruna' => Pengurus::where('pengurusable_type', 'App\Models\KarangTaruna')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($desaIds) {
                            $query->whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                    'lpm' => Pengurus::where('pengurusable_type', 'App\Models\Lpm')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($desaIds) {
                            $query->whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                    'satlinmas' => Pengurus::where('pengurusable_type', 'App\Models\Satlinmas')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($desaIds) {
                            $query->whereIn('desa_id', $desaIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                ]
            ];

            // Stats untuk kelurahan (hanya yang aktif)
            $kelurahanStats = [
                'count' => $totalKelurahan,
                'rw' => Rw::whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif')->count(),
                'rt' => Rt::whereHas('rw', function ($query) use ($kelurahanIds) {
                    $query->whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif');
                })->where('status_kelembagaan', 'aktif')->count(),
                'posyandu' => Posyandu::whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif')->count(),
                'karangTaruna' => KarangTaruna::whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif')->count(),
                'lpm' => Lpm::whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif')->count(),
                'satlinmas' => Satlinmas::whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif')->count(),
                'pengurus' => [
                    'rw' => Pengurus::where('pengurusable_type', 'App\Models\Rw')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($kelurahanIds) {
                            $query->whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                    'rt' => Pengurus::where('pengurusable_type', 'App\Models\Rt')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($kelurahanIds) {
                            $query->where('status_kelembagaan', 'aktif')
                                ->whereIn('desa_id', $kelurahanIds);
                        })->count(),
                    'posyandu' => Pengurus::where('pengurusable_type', 'App\Models\Posyandu')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($kelurahanIds) {
                            $query->whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                    'karangTaruna' => Pengurus::where('pengurusable_type', 'App\Models\KarangTaruna')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($kelurahanIds) {
                            $query->whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                    'lpm' => Pengurus::where('pengurusable_type', 'App\Models\Lpm')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($kelurahanIds) {
                            $query->whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                    'satlinmas' => Pengurus::where('pengurusable_type', 'App\Models\Satlinmas')
                        ->where('status_jabatan', 'aktif')
                        ->whereHas('pengurusable', function ($query) use ($kelurahanIds) {
                            $query->whereIn('desa_id', $kelurahanIds)->where('status_kelembagaan', 'aktif');
                        })->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Summary kelembagaan berhasil diambil',
                'data' => [
                    'overview' => [
                        'kecamatan' => $totalKecamatan,
                        'desa_kelurahan_total' => $totalDesaKelurahan,
                        'desa' => $totalDesa,
                        'kelurahan' => $totalKelurahan,
                    ],
                    'total_kelembagaan' => [
                        'rw' => $totalRw,
                        'rt' => $totalRt,
                        'posyandu' => $totalPosyandu,
                        'karangTaruna' => $totalKarangTaruna,
                        'lpm' => $totalLpm,
                        'satlinmas' => $totalSatlinmas,
                    ],
                    'total_pengurus' => [
                        'rw' => $totalPengurusRw,
                        'rt' => $totalPengurusRt,
                        'posyandu' => $totalPengurusPosyandu,
                        'karangTaruna' => $totalPengurusKarangTaruna,
                        'lpm' => $totalPengurusLpm,
                        'satlinmas' => $totalPengurusSatlinmas,
                    ],
                    'by_status' => [
                        'desa' => $desaStats,
                        'kelurahan' => $kelurahanStats,
                    ],
                    'formation_stats' => [
                        'karangTaruna' => [
                            'total' => $totalKarangTaruna,
                            'desa_terbentuk' => $desaWithKarangTaruna,
                            'persentase' => $totalDesaKelurahan > 0 ? round(($desaWithKarangTaruna / $totalDesaKelurahan) * 100, 2) : 0
                        ],
                        'lpm' => [
                            'total' => $totalLpm,
                            'desa_terbentuk' => $desaWithLpm,
                            'persentase' => $totalDesaKelurahan > 0 ? round(($desaWithLpm / $totalDesaKelurahan) * 100, 2) : 0
                        ],
                        'satlinmas' => [
                            'total' => $totalSatlinmas,
                            'desa_terbentuk' => $desaWithSatlinmas,
                            'persentase' => $totalDesaKelurahan > 0 ? round(($desaWithSatlinmas / $totalDesaKelurahan) * 100, 2) : 0
                        ]
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil summary kelembagaan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get kelembagaan data for specific kecamatan
     */
    public function byKecamatan($kecamatanId)
    {
        try {
            $kecamatan = Kecamatan::with([
                'desas' => function ($query) {
                    $query->select('id', 'nama', 'kecamatan_id', 'status_pemerintahan');
                }
            ])->findOrFail($kecamatanId);

            $desaIds = $kecamatan->desas->pluck('id');

            // Get all kelembagaan data for this kecamatan's desas
            $rws = Rw::whereIn('desa_id', $desaIds)->with('rts')->get();
            $posyandus = Posyandu::whereIn('desa_id', $desaIds)->get();
            $karangTarunas = KarangTaruna::whereIn('desa_id', $desaIds)->get();
            $lpms = Lpm::whereIn('desa_id', $desaIds)->get();
            $satlinmas = Satlinmas::whereIn('desa_id', $desaIds)->get();

            // Process each desa
            $desasWithKelembagaan = $kecamatan->desas->map(function ($desa) use ($rws, $posyandus, $karangTarunas, $lpms, $satlinmas) {
                $desaRws = $rws->where('desa_id', $desa->id);
                $desaPosyandus = $posyandus->where('desa_id', $desa->id);
                $desaKarangTaruna = $karangTarunas->where('desa_id', $desa->id)->first();
                $desaLpm = $lpms->where('desa_id', $desa->id)->first();
                $desaSatlinmas = $satlinmas->where('desa_id', $desa->id)->first();

                // Calculate total RTs
                $totalRts = $desaRws->sum(function ($rw) {
                    return $rw->rts->count();
                });

                return [
                    'id' => $desa->id,
                    'nama' => $desa->nama,
                    'status_pemerintahan' => $desa->status_pemerintahan,
                    'kelembagaan' => [
                        'rw' => $desaRws->count(),
                        'rt' => $totalRts,
                        'posyandu' => $desaPosyandus->count(),
                        'karangTaruna' => $desaKarangTaruna ? 'Terbentuk' : 'Belum Terbentuk',
                        'lpm' => $desaLpm ? 'Terbentuk' : 'Belum Terbentuk',
                        'satlinmas' => $desaSatlinmas ? 'Terbentuk' : 'Belum Terbentuk',
                    ]
                ];
            });

            // Calculate totals for kecamatan
            $totalKelembagaan = $desasWithKelembagaan->reduce(function ($carry, $desa) {
                return [
                    'rw' => $carry['rw'] + $desa['kelembagaan']['rw'],
                    'rt' => $carry['rt'] + $desa['kelembagaan']['rt'],
                    'posyandu' => $carry['posyandu'] + $desa['kelembagaan']['posyandu'],
                    'karangTaruna' => $carry['karangTaruna'] + ($desa['kelembagaan']['karangTaruna'] === 'Terbentuk' ? 1 : 0),
                    'lpm' => $carry['lpm'] + ($desa['kelembagaan']['lpm'] === 'Terbentuk' ? 1 : 0),
                    'satlinmas' => $carry['satlinmas'] + ($desa['kelembagaan']['satlinmas'] === 'Terbentuk' ? 1 : 0),
                ];
            }, ['rw' => 0, 'rt' => 0, 'posyandu' => 0, 'karangTaruna' => 0, 'lpm' => 0, 'satlinmas' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Data kelembagaan kecamatan berhasil diambil',
                'data' => [
                    'id' => $kecamatan->id,
                    'nama' => $kecamatan->nama,
                    'desas' => $desasWithKelembagaan,
                    'totalKelembagaan' => $totalKelembagaan,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kelembagaan kecamatan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get kelembagaan summary for specific desa
     */
    public function summaryByDesa($desaId)
    {
        try {
            // Validate desa exists
            $desa = Desa::find($desaId);
            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            // Count kelembagaan for this desa
            $rwCount = Rw::where('desa_id', $desaId)->count();
            $rtCount = Rt::whereHas('rw', function ($query) use ($desaId) {
                $query->where('desa_id', $desaId);
            })->count();
            $posyanduCount = Posyandu::where('desa_id', $desaId)->count();

            // Check kelembagaan formation status
            $karangTaruna = KarangTaruna::where('desa_id', $desaId)->first();
            $lpm = Lpm::where('desa_id', $desaId)->first();
            $satlinmas = Satlinmas::where('desa_id', $desaId)->first();
            $pkk = \App\Models\Pkk::where('desa_id', $desaId)->first();

            // Count kelembagaan
            $karangTarunaCount = $karangTaruna ? 1 : 0;
            $lpmCount = $lpm ? 1 : 0;
            $satlinmasCount = $satlinmas ? 1 : 0;
            $pkkCount = $pkk ? 1 : 0;

            $summary = [
                'rt' => $rtCount,
                'rw' => $rwCount,
                'posyandu' => $posyanduCount,
                'karang_taruna' => $karangTarunaCount,
                'lpm' => $lpmCount,
                'satlinmas' => $satlinmasCount,
                'pkk' => $pkkCount,
                'karang_taruna_formed' => (bool) $karangTaruna,
                'lpm_formed' => (bool) $lpm,
                'satlinmas_formed' => (bool) $satlinmas,
                'pkk_formed' => (bool) $pkk,
            ];

            // Calculate total
            $summary['total'] = $rtCount + $rwCount + $posyanduCount + $karangTarunaCount + $lpmCount + $satlinmasCount + $pkkCount;

            return response()->json([
                'success' => true,
                'message' => 'Summary kelembagaan desa berhasil diambil',
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil summary kelembagaan desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RW list for specific desa
     */
    public function getDesaRW($desaId)
    {
        try {
            $desa = Desa::find($desaId);
            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $rwList = Rw::where('desa_id', $desaId)
                ->with(['pengurus', 'rts' => function ($query) {
                    $query->with('pengurus');
                }])
                ->get()
                ->map(function ($rw) {
                    $ketua = $rw->pengurus()->where('jabatan', 'Ketua')->first();
                    return [
                        'id' => $rw->id,
                        'nama' => 'RW ' . $rw->nomor,
                        'nomor' => $rw->nomor,
                        'alamat' => $rw->alamat,
                        'ketua' => $ketua ? $ketua->nama : '-',
                        'pengurus_count' => $rw->pengurus->count(),
                        'rt_count' => $rw->rts->count(),
                        'total_pengurus_rt' => $rw->rts->sum(function ($rt) {
                            return $rt->pengurus->count();
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Data RW berhasil diambil',
                'data' => $rwList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data RW',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List RT with optional desa_id parameter for admin access
     */
    public function listRT(Request $request)
    {
        try {
            $desaId = $request->get('desa_id');

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter desa_id diperlukan'
                ], 400);
            }

            $desa = Desa::find($desaId);
            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $rtList = Rt::where('desa_id', $desaId)
                ->with(['pengurus' => function ($query) {
                    $query->where(function ($q) {
                        $q->where('jabatan', 'Ketua RT')
                            ->orWhere('jabatan', 'LIKE', '%Ketua%');
                    })
                        ->where('status_jabatan', 'aktif')
                        ->select('pengurusable_id', 'nama_lengkap');
                }, 'rw:id,nomor'])
                ->orderBy('nomor')
                ->get()
                ->map(function ($item) {
                    // Get ketua name from pengurus relation
                    $ketua = $item->pengurus->first();
                    $item->ketua_nama = $ketua ? $ketua->nama_lengkap : null;
                    unset($item->pengurus); // Remove pengurus relation from response
                    return $item;
                });

            return response()->json([
                'success' => true,
                'data' => $rtList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data RT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List Posyandu with optional desa_id parameter for admin access
     */
    public function listPosyandu(Request $request)
    {
        try {
            $desaId = $request->get('desa_id');

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter desa_id diperlukan'
                ], 400);
            }

            $posyanduList = Posyandu::where('desa_id', $desaId)
                ->with(['pengurus' => function ($query) {
                    $query->where('status_jabatan', 'aktif')
                        ->select('pengurusable_id', 'nama_lengkap');
                }])
                ->orderBy('nama')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $posyanduList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Posyandu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List Karang Taruna with optional desa_id parameter for admin access
     */
    public function listKarangTaruna(Request $request)
    {
        try {
            $desaId = $request->get('desa_id');

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter desa_id diperlukan'
                ], 400);
            }

            $karangTarunaList = KarangTaruna::where('desa_id', $desaId)
                ->with(['pengurus' => function ($query) {
                    $query->where('status_jabatan', 'aktif')
                        ->select('pengurusable_id', 'nama_lengkap');
                }])
                ->orderBy('nama')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $karangTarunaList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Karang Taruna',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List LPM with optional desa_id parameter for admin access
     */
    public function listLPM(Request $request)
    {
        try {
            $desaId = $request->get('desa_id');

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter desa_id diperlukan'
                ], 400);
            }

            $lpmList = Lpm::where('desa_id', $desaId)
                ->with(['pengurus' => function ($query) {
                    $query->where('status_jabatan', 'aktif')
                        ->select('pengurusable_id', 'nama_lengkap');
                }])
                ->orderBy('nama')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $lpmList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data LPM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List Satlinmas with optional desa_id parameter for admin access
     */
    public function listSatlinmas(Request $request)
    {
        try {
            $desaId = $request->get('desa_id');

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter desa_id diperlukan'
                ], 400);
            }

            $satlinmasList = Satlinmas::where('desa_id', $desaId)
                ->with(['pengurus' => function ($query) {
                    $query->where('status_jabatan', 'aktif')
                        ->select('pengurusable_id', 'nama_lengkap');
                }])
                ->orderBy('nama')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $satlinmasList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Satlinmas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List PKK with optional desa_id parameter for admin access
     */
    public function listPKK(Request $request)
    {
        try {
            $desaId = $request->get('desa_id');

            if (!$desaId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter desa_id diperlukan'
                ], 400);
            }

            $pkkList = Pkk::where('desa_id', $desaId)
                ->with(['pengurus' => function ($query) {
                    $query->where('status_jabatan', 'aktif')
                        ->select('pengurusable_id', 'nama_lengkap');
                }])
                ->orderBy('nama')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $pkkList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data PKK',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get RT list for specific desa
     */
    public function getDesaRT($desaId)
    {
        try {
            $desa = Desa::find($desaId);
            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $rtList = Rt::where('desa_id', $desaId)
                ->with(['pengurus', 'rw'])
                ->get()
                ->map(function ($rt) {
                    $ketua = $rt->pengurus()->where('jabatan', 'LIKE', '%Ketua%')->first();
                    return [
                        'id' => $rt->id,
                        'nama' => 'RT ' . $rt->nomor . ' RW ' . ($rt->rw ? $rt->rw->nomor : '-'),
                        'nomor' => $rt->nomor,
                        'rw_nomor' => $rt->rw ? $rt->rw->nomor : null,
                        'alamat' => $rt->alamat,
                        'ketua' => $ketua ? $ketua->nama : '-',
                        'pengurus_count' => $rt->pengurus->count(),
                        'created_at' => $rt->created_at,
                        'updated_at' => $rt->updated_at
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Data RT berhasil diambil',
                'data' => $rtList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data RT',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show individual RW detail for admin access
     */
    public function showRW($id)
    {
        try {
            $rw = Rw::with(['desa', 'rts.pengurus', 'pengurus.produkHukum'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data RW berhasil diambil',
                'data' => $rw
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data RW',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Show individual RT detail for admin access
     */
    public function showRT($id)
    {
        try {
            $rt = Rt::with(['desa', 'rw', 'pengurus.produkHukum'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data RT berhasil diambil',
                'data' => $rt
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data RT',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Show individual Posyandu detail for admin access
     */
    public function showPosyandu($id)
    {
        try {
            $posyandu = Posyandu::with(['desa', 'pengurus.produkHukum'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data Posyandu berhasil diambil',
                'data' => $posyandu
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Posyandu',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Show individual Karang Taruna detail for admin access
     */
    public function showKarangTaruna($id)
    {
        try {
            $karangTaruna = KarangTaruna::with(['desa', 'pengurus.produkHukum'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data Karang Taruna berhasil diambil',
                'data' => $karangTaruna
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Karang Taruna',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Show individual LPM detail for admin access
     */
    public function showLPM($id)
    {
        try {
            $lpm = Lpm::with(['desa', 'pengurus.produkHukum'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data LPM berhasil diambil',
                'data' => $lpm
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data LPM',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Show individual Satlinmas detail for admin access
     */
    public function showSatlinmas($id)
    {
        try {
            $satlinmas = Satlinmas::with(['desa', 'pengurus.produkHukum'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data Satlinmas berhasil diambil',
                'data' => $satlinmas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Satlinmas',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Show individual PKK detail for admin access
     */
    public function showPKK($id)
    {
        try {
            $pkk = Pkk::with(['desa', 'pengurus.produkHukum'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data PKK berhasil diambil',
                'data' => $pkk
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data PKK',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get Posyandu list for specific desa
     */
    public function getDesaPosyandu($desaId)
    {
        try {
            $desa = Desa::find($desaId);
            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $posyanduList = Posyandu::where('desa_id', $desaId)
                ->with('pengurus')
                ->get()
                ->map(function ($posyandu) {
                    $ketua = $posyandu->pengurus()->where('jabatan', 'Ketua')->first();
                    return [
                        'id' => $posyandu->id,
                        'nama' => $posyandu->nama,
                        'alamat' => $posyandu->alamat,
                        'ketua' => $ketua ? $ketua->nama : '-',
                        'pengurus_count' => $posyandu->pengurus->count(),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Data Posyandu berhasil diambil',
                'data' => $posyanduList
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Posyandu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Karang Taruna for specific desa
     */
    public function getDesaKarangTaruna($desaId)
    {
        try {
            $desa = Desa::find($desaId);
            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $karangTaruna = KarangTaruna::where('desa_id', $desaId)
                ->with('pengurus')
                ->first();

            $data = null;
            if ($karangTaruna) {
                $ketua = $karangTaruna->pengurus()->where('jabatan', 'Ketua')->first();
                $data = [
                    'id' => $karangTaruna->id,
                    'nama' => $karangTaruna->nama,
                    'alamat' => $karangTaruna->alamat,
                    'ketua' => $ketua ? $ketua->nama : '-',
                    'pengurus_count' => $karangTaruna->pengurus->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Karang Taruna berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Karang Taruna',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get LPM for specific desa
     */
    public function getDesaLPM($desaId)
    {
        try {
            $desa = Desa::find($desaId);
            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $lpm = Lpm::where('desa_id', $desaId)
                ->with('pengurus')
                ->first();

            $data = null;
            if ($lpm) {
                $ketua = $lpm->pengurus()->where('jabatan', 'Ketua')->first();
                $data = [
                    'id' => $lpm->id,
                    'nama' => $lpm->nama,
                    'alamat' => $lpm->alamat,
                    'ketua' => $ketua ? $ketua->nama : '-',
                    'pengurus_count' => $lpm->pengurus->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data LPM berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data LPM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Satlinmas for specific desa
     */
    public function getDesaSatlinmas($desaId)
    {
        try {
            $desa = Desa::find($desaId);
            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $satlinmas = Satlinmas::where('desa_id', $desaId)
                ->with('pengurus')
                ->first();

            $data = null;
            if ($satlinmas) {
                $ketua = $satlinmas->pengurus()->where('jabatan', 'Ketua')->first();
                $data = [
                    'id' => $satlinmas->id,
                    'nama' => $satlinmas->nama,
                    'alamat' => $satlinmas->alamat,
                    'ketua' => $ketua ? $ketua->nama : '-',
                    'pengurus_count' => $satlinmas->pengurus->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data Satlinmas berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data Satlinmas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get PKK for specific desa
     */
    public function getDesaPKK($desaId)
    {
        try {
            $desa = Desa::find($desaId);
            if (!$desa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Desa tidak ditemukan'
                ], 404);
            }

            $pkk = Pkk::where('desa_id', $desaId)
                ->with('pengurus')
                ->first();

            $data = null;
            if ($pkk) {
                $ketua = $pkk->pengurus()->where('jabatan', 'Ketua')->first();
                $data = [
                    'id' => $pkk->id,
                    'nama' => $pkk->nama,
                    'alamat' => $pkk->alamat,
                    'ketua' => $ketua ? $ketua->nama : '-',
                    'pengurus_count' => $pkk->pengurus->count(),
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data PKK berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data PKK',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pengurus by kelembagaan for admin access
     */
    public function getPengurusByKelembagaan(Request $request)
    {
        try {
            $kelembagaanType = $request->query('kelembagaan_type');
            $kelembagaanId = $request->query('kelembagaan_id');

            if (!$kelembagaanType || !$kelembagaanId) {
                return response()->json([
                    'success' => false,
                    'message' => 'kelembagaan_type dan kelembagaan_id diperlukan'
                ], 400);
            }

            // Get only essential pengurus data for list view
            $pengurus = Pengurus::where('pengurusable_type', $kelembagaanType)
                ->where('pengurusable_id', $kelembagaanId)
                ->where('status_jabatan', 'aktif')
                ->select('id', 'nama_lengkap', 'jabatan', 'status_jabatan', 'avatar', 'desa_id')
                ->with('desa:id,nama')
                ->orderBy('jabatan')
                ->get();

            return response()->json(['success' => true, 'data' => $pengurus]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengurus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pengurus history for admin access
     */
    public function getPengurusHistory(Request $request)
    {
        try {
            $kelembagaanType = $request->query('kelembagaan_type');
            $kelembagaanId = $request->query('kelembagaan_id');

            if (!$kelembagaanType || !$kelembagaanId) {
                return response()->json([
                    'success' => false,
                    'message' => 'kelembagaan_type dan kelembagaan_id diperlukan'
                ], 400);
            }

            // Get inactive pengurus for history
            $pengurus = Pengurus::where('pengurusable_type', $kelembagaanType)
                ->where('pengurusable_id', $kelembagaanId)
                ->where('status_jabatan', 'selesai')
                ->select('id', 'nama_lengkap', 'jabatan', 'status_jabatan', 'tanggal_mulai_jabatan', 'tanggal_akhir_jabatan', 'avatar', 'desa_id')
                ->with('desa:id,nama')
                ->orderBy('tanggal_akhir_jabatan', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $pengurus]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data history pengurus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show individual pengurus detail for admin access
     */
    public function showPengurus($id)
    {
        try {
            $pengurus = Pengurus::with(['desa', 'produkHukum'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data pengurus berhasil diambil',
                'data' => $pengurus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengurus',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
