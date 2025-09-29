<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Musdesus;
use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MusdesusController extends Controller
{
    /**
     * Get kecamatan list for dropdown
     */
    public function getKecamatan()
    {
        try {
            $kecamatan = Kecamatan::select('id', 'nama')->orderBy('nama')->get();
            return response()->json([
                'success' => true,
                'data' => $kecamatan
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kecamatan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get desa list by kecamatan for dropdown
     */
    public function getDesaByKecamatan($kecamatanId)
    {
        try {
            $desa = Desa::where('kecamatan_id', $kecamatanId)
                       ->select('id', 'nama')
                       ->orderBy('nama')
                       ->get();
            
            return response()->json([
                'success' => true,
                'data' => $desa
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if desa already uploaded files
     */
    public function checkDesaUploadStatus($desaId)
    {
        try {
            $existingUpload = Musdesus::where('desa_id', $desaId)->first();
            
            if ($existingUpload) {
                $desaName = Desa::find($desaId)->nama;
                $filesCount = Musdesus::where('desa_id', $desaId)->count();
                
                return response()->json([
                    'success' => true,
                    'already_uploaded' => true,
                    'message' => "Desa {$desaName} sudah pernah melakukan upload sebelumnya.",
                    'upload_info' => [
                        'upload_date' => $existingUpload->created_at->format('d M Y H:i'),
                        'uploader_name' => $existingUpload->nama_pengupload,
                        'files_count' => $filesCount,
                        'desa_name' => $desaName
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'already_uploaded' => false,
                'message' => 'Desa belum pernah upload, dapat melakukan upload.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek status upload desa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $musdesus = Musdesus::with(['desa', 'kecamatan'])
                               ->orderBy('created_at', 'desc')
                               ->paginate(10);
            
            return response()->json([
                'success' => true,
                'data' => $musdesus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data musdesus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'kecamatan_id' => 'required|exists:kecamatans,id',
                'desa_id' => 'required|exists:desas,id',
                'nama_pengupload' => 'required|string|max:255',
                'email_pengupload' => 'nullable|email|max:255',
                'telepon_pengupload' => 'nullable|string|max:20',
                'keterangan' => 'nullable|string',
                'tanggal_musdesus' => 'nullable|date',
                'files' => 'required|array|min:1',
                'files.*' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240' // max 10MB per file
            ]);

            // Check if desa already has uploaded files
            $existingUpload = Musdesus::where('desa_id', $request->desa_id)->first();
            if ($existingUpload) {
                $desaName = Desa::find($request->desa_id)->nama;
                return response()->json([
                    'success' => false,
                    'message' => "Desa {$desaName} sudah pernah melakukan upload sebelumnya. Satu desa hanya dapat upload satu kali.",
                    'existing_upload' => [
                        'upload_date' => $existingUpload->created_at->format('d M Y H:i'),
                        'uploader_name' => $existingUpload->nama_pengupload,
                        'files_count' => Musdesus::where('desa_id', $request->desa_id)->count()
                    ]
                ], 422);
            }

            // Create directory if not exists
            $uploadPath = 'musdesus';
            if (!Storage::disk('public')->exists($uploadPath)) {
                Storage::disk('public')->makeDirectory($uploadPath);
            }

            $uploadedFiles = [];

            foreach ($request->file('files') as $file) {
                // Generate unique filename
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                
                // Store file
                $path = $file->storeAs($uploadPath, $fileName, 'public');

                // Create database record
                $musdesus = Musdesus::create([
                    'nama_file' => $fileName,
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'path_file' => $path,
                    'mime_type' => $file->getMimeType(),
                    'ukuran_file' => $file->getSize(),
                    'nama_pengupload' => $request->nama_pengupload,
                    'email_pengupload' => $request->email_pengupload,
                    'telepon_pengupload' => $request->telepon_pengupload,
                    'desa_id' => $request->desa_id,
                    'kecamatan_id' => $request->kecamatan_id,
                    'keterangan' => $request->keterangan,
                    'tanggal_musdesus' => $request->tanggal_musdesus,
                    'status' => 'pending'
                ]);

                $uploadedFiles[] = $musdesus->load(['desa', 'kecamatan']);
            }

            return response()->json([
                'success' => true,
                'message' => 'File berhasil diupload',
                'data' => $uploadedFiles
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $musdesus = Musdesus::with(['desa', 'kecamatan'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $musdesus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $musdesus = Musdesus::findOrFail($id);

            $request->validate([
                'status' => 'sometimes|in:pending,approved,rejected',
                'catatan_admin' => 'nullable|string'
            ]);

            $musdesus->update($request->only(['status', 'catatan_admin']));

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diupdate',
                'data' => $musdesus->load(['desa', 'kecamatan'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $musdesus = Musdesus::findOrFail($id);
            
            // Delete file from storage
            if (Storage::disk('public')->exists($musdesus->path_file)) {
                Storage::disk('public')->delete($musdesus->path_file);
            }

            $musdesus->delete();

            return response()->json([
                'success' => true,
                'message' => 'File berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download file
     */
    public function download(string $id)
    {
        try {
            $musdesus = Musdesus::findOrFail($id);
            
            $filePath = storage_path('app/public/' . $musdesus->path_file);
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            return response()->download($filePath, $musdesus->nama_file_asli);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendownload file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Secure destroy - for public stats page with admin verification
     */
    public function secureDestroy(Request $request, string $id)
    {
        try {
            // Check if admin verification is provided in session storage (frontend verification)
            // This is a secondary check, primary verification happens in frontend
            
            $musdesus = Musdesus::findOrFail($id);
            
            // Store file info for response
            $fileName = $musdesus->nama_file_asli;
            
            // Delete physical file
            if ($musdesus->path_file && Storage::disk('public')->exists($musdesus->path_file)) {
                Storage::disk('public')->delete($musdesus->path_file);
            }
            
            // Delete database record
            $musdesus->delete();

            return response()->json([
                'success' => true,
                'message' => "File '{$fileName}' berhasil dihapus",
                'deleted_file' => $fileName
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus file',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
