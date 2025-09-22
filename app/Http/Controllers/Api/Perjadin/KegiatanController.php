<?php

// dpmd-backend/app/Http/Controllers/Api/Perjadin/KegiatanController.php
namespace App\Http\Controllers\Api\Perjadin;

use App\Http\Controllers\Controller;
use App\Models\Perjadin\Kegiatan;
use App\Models\Perjadin\KegiatanBidang;
use App\Services\KegiatanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class KegiatanController extends Controller
{
    // protected $kegiatanService;

    // public function __construct(KegiatanService $kegiatanService)
    // {
    //     $this->kegiatanService = $kegiatanService;
    // }

    public function index(Request $request)
    {
        $query = Kegiatan::with('details.bidang')
            ->latest('tanggal_mulai');

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->where('nama_kegiatan', 'like', $search)
                  ->orWhere('nomor_sp', 'like', $search)
                  ->orWhere('lokasi', 'like', $search)
                  ->orWhere('keterangan', 'like', $search);
            })->orWhereHas('details', function ($q) use ($search) {
                $q->where('personil', 'like', $search);
            });
        }
        
        if ($request->filled('id_bidang')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('id_bidang', $request->id_bidang);
            });
        }
        
        if ($request->date_filter === 'mingguan') {
            $query->whereBetween('tanggal_mulai', [now()->startOfWeek(), now()->endOfWeek()]);
        }
        if ($request->date_filter === 'bulanan') {
            $query->whereBetween('tanggal_mulai', [now()->startOfMonth(), now()->endOfMonth()]);
        }

        $kegiatan = $query->paginate($request->limit ?? 5);

        return response()->json([
            'data' => $kegiatan->items(),
            'total' => $kegiatan->total(),
            'current_page' => $kegiatan->currentPage(),
            'last_page' => $kegiatan->lastPage(),
        ]);
    }

    public function show($id)
    {
        $kegiatan = Kegiatan::with('details.bidang')
            ->find($id);

        if (!$kegiatan) {
            return response()->json(['status' => 'error', 'message' => 'Kegiatan tidak ditemukan.'], 404);
        }

        return response()->json($kegiatan);
    }

    public function store(Request $request)
    {
        // $conflictMessage = $this->kegiatanService->checkPersonilConflict(
        //     $request->personil_bidang_list,
        //     $request->tanggal_mulai,
        //     $request->tanggal_selesai
        // );

        // if ($conflictMessage) {
        //     return response()->json(['status' => 'error', 'message' => $conflictMessage], 409);
        // }

        DB::beginTransaction();
        try {
            $kegiatan = Kegiatan::create($request->all());

            foreach ($request->personil_bidang_list as $item) {
                if (!empty($item['personil'])) {
                    KegiatanBidang::create([
                        'id_kegiatan' => $kegiatan->id_kegiatan,
                        'id_bidang' => $item['id_bidang'],
                        'personil' => implode(', ', $item['personil']),
                    ]);
                }
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Kegiatan berhasil ditambahkan.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => 'Gagal menambahkan kegiatan. Error: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        // $conflictMessage = $this->kegiatanService->checkPersonilConflict(
        //     $request->personil_bidang_list,
        //     $request->tanggal_mulai,
        //     $request->tanggal_selesai,
        //     $id
        // );

        // if ($conflictMessage) {
        //     return response()->json(['status' => 'error', 'message' => $conflictMessage], 409);
        // }

        DB::beginTransaction();
        try {
            $kegiatan = Kegiatan::findOrFail($id);
            $kegiatan->update($request->all());
            $kegiatan->details()->delete();

            foreach ($request->personil_bidang_list as $item) {
                if (!empty($item['personil'])) {
                    KegiatanBidang::create([
                        'id_kegiatan' => $kegiatan->id_kegiatan,
                        'id_bidang' => $item['id_bidang'],
                        'personil' => implode(', ', $item['personil']),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Kegiatan berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => 'Gagal memperbarui kegiatan. Error: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $kegiatan = Kegiatan::findOrFail($id);
            $kegiatan->details()->delete();
            $kegiatan->delete();

            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Kegiatan berhasil dihapus.']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus kegiatan. Error: ' . $e->getMessage()]);
        }
    }
    
    public function exportExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Kegiatan');
        
        $headers = ['Nomor', 'Nama Kegiatan', 'Nomor SP', 'Tanggal', 'Tempat', 'Bidang', 'Personil', 'Keterangan'];
        $sheet->fromArray([$headers], NULL, 'A1');
        
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF121A4B']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $data = Kegiatan::with('details.bidang')
            ->orderBy('tanggal_mulai', 'desc')
            ->get();
        
        $rowData = [];
        $no = 1;
        foreach ($data as $row) {
            $tanggal = date('d-m-Y', strtotime($row->tanggal_mulai)) . ' s.d. ' . date('d-m-Y', strtotime($row->tanggal_selesai));
            
            $bidangList = implode(', ', $row->details->map(fn($detail) => $detail->bidang->nama)->toArray());
            $personilList = implode(', ', $row->details->map(fn($detail) => $detail->personil)->toArray());
            
            $rowData[] = [
                $no++,
                $row->nama_kegiatan,
                $row->nomor_sp,
                $tanggal,
                $row->lokasi,
                $bidangList,
                $personilList,
                $row->keterangan,
            ];
        }
        $sheet->fromArray($rowData, NULL, 'A2');
        
        $dataStyle = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]];
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle('A2:H' . $highestRow)->applyFromArray($dataStyle);
        
        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="rekap_kegiatan.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}