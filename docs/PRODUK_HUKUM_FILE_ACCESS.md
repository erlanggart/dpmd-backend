# Produk Hukum File Access - Dokumentasi

## 📁 Lokasi Penyimpanan File

### Development (Local)
- **Path Storage**: `storage/app/uploads/produk_hukum/`
- **Akses File**: Melalui endpoint API
- **URL Format**: `http://localhost:8000/api/desa/produk-hukum/{id}/download`

### Production
- **Path Storage**: `storage/app/uploads/produk_hukum/`
- **Akses File**: Melalui endpoint API
- **URL Format**: `https://dpmdbogorkab.id/api/desa/produk-hukum/{id}/download`

## 🔧 Konfigurasi Disk Storage

File disimpan menggunakan disk `public_uploads` yang dikonfigurasi di `config/filesystems.php`:

```php
'public_uploads' => [
    'driver' => 'local',
    'root' => storage_path('app/uploads'),
    'url' => env('APP_URL') . '/uploads',
    'visibility' => 'public',
],
```

## 📝 Backend Implementation

### ProdukHukumController - Store Method
```php
public function store(Request $request)
{
    // Simpan file ke disk 'public_uploads' di folder 'produk_hukum'
    $file = $request->file('file');
    $path = $file->store('produk_hukum', 'public_uploads');
    
    // Simpan hanya nama file (bukan path lengkap)
    $fileName = basename($path);
    
    $produkHukum = ProdukHukum::create([
        'desa_id' => $user->desa_id,
        'file' => $fileName, // Hanya nama file
        // ... field lainnya
    ]);
}
```

### ProdukHukumController - Download Method
```php
public function downloadFile($id)
{
    $produkHukum = ProdukHukum::find($id);
    
    // File path: produk_hukum/nama_file.pdf
    $filePath = 'produk_hukum/' . $produkHukum->file;
    
    // Ambil file dari storage/app/uploads/
    if (!Storage::disk('public_uploads')->exists($filePath)) {
        return response()->json(['message' => 'File tidak ada'], 404);
    }
    
    // Return file untuk ditampilkan di browser
    return response()->file(
        Storage::disk('public_uploads')->path($filePath),
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $produkHukum->file . '"'
        ]
    );
}
```

## 🎨 Frontend Implementation

### ProdukHukumDetail.jsx
```jsx
const ProdukHukumDetail = () => {
    const { id } = useParams();
    const [produkHukum, setProdukHukum] = useState(null);
    
    // Gunakan endpoint download API
    const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';
    const fileUrl = `${apiUrl}/api/desa/produk-hukum/${produkHukum.id}/download`;
    
    return (
        <iframe
            src={fileUrl}
            title={produkHukum.judul}
            className="w-full h-screen"
        />
    );
};
```

## 🔐 Route Configuration

```php
// routes/api.php

// Desa Routes (Protected dengan auth:sanctum)
Route::prefix('desa')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('/produk-hukum', ProdukHukumController::class);
    Route::get('/produk-hukum/{id}/download', [ProdukHukumController::class, 'downloadFile']);
});
```

## 📋 Environment Variables

### .env (Local)
```env
APP_URL=http://localhost:8000
VITE_API_URL=http://localhost:8000
```

### .env (Production)
```env
APP_URL=https://dpmdbogorkab.id
VITE_API_URL=https://dpmdbogorkab.id
```

## ✅ Testing Checklist

### 1. Upload File
- [ ] File tersimpan di `storage/app/uploads/produk_hukum/`
- [ ] Nama file unik (menggunakan Storage::put)
- [ ] Database menyimpan hanya nama file (bukan path lengkap)

### 2. Download File
- [ ] URL: `GET /api/desa/produk-hukum/{id}/download`
- [ ] Response: File PDF dengan Content-Type application/pdf
- [ ] Browser menampilkan PDF inline (tidak download)

### 3. View File di Detail Page
- [ ] Iframe menampilkan PDF dari endpoint download
- [ ] URL benar sesuai environment (local vs production)
- [ ] Tidak ada error 404

## 🐛 Common Issues & Solutions

### Issue 1: Error 404 File Not Found
**Symptom**: `GET http://localhost:8000/uploads/produk_hukum/xxx.pdf 404`

**Cause**: Frontend mencoba akses file langsung tanpa melalui endpoint API

**Solution**: 
- ✅ Gunakan endpoint: `/api/desa/produk-hukum/{id}/download`
- ❌ Jangan gunakan: `/uploads/produk_hukum/xxx.pdf`

### Issue 2: File Tidak Tersimpan
**Symptom**: File tidak ada di `storage/app/uploads/produk_hukum/`

**Cause**: Salah disk atau path

**Solution**:
```php
// BENAR
$path = $file->store('produk_hukum', 'public_uploads');

// SALAH
$path = $file->store('produk_hukum', 'public'); // Salah disk
```

### Issue 3: Nama File dengan Path Lengkap di Database
**Symptom**: Database menyimpan `produk_hukum/xxx.pdf` atau `storage/app/uploads/produk_hukum/xxx.pdf`

**Cause**: Tidak ekstrak basename dari path

**Solution**:
```php
// BENAR
$fileName = basename($path); // Hanya: xxx.pdf

// SALAH
$fileName = $path; // produk_hukum/xxx.pdf
```

## 📊 Database Schema

```sql
CREATE TABLE produk_hukums (
    id CHAR(36) PRIMARY KEY,
    desa_id CHAR(36),
    judul VARCHAR(255),
    nomor VARCHAR(255),
    tahun VARCHAR(4),
    jenis ENUM('Peraturan Desa', 'Peraturan Kepala Desa', 'Keputusan Kepala Desa'),
    file VARCHAR(255), -- Hanya nama file: UOYe1nTDj93yjVE09TehlZoX06uPnWdQGihx6HL5.pdf
    -- ... field lainnya
);
```

## 🔄 Migration Notes

Jika sebelumnya file disimpan di lokasi berbeda:

### Old Implementation (WRONG)
- File di: `public/uploads/produk_hukum/`
- Akses: Direct URL `http://localhost:8000/uploads/produk_hukum/xxx.pdf`
- Database: Simpan nama file atau path lengkap

### New Implementation (CORRECT)
- File di: `storage/app/uploads/produk_hukum/`
- Akses: Via endpoint `http://localhost:8000/api/desa/produk-hukum/{id}/download`
- Database: Simpan hanya nama file

## 🚀 Deployment Checklist

### Backend
- [ ] File permissions: `chmod -R 775 storage/app/uploads`
- [ ] Owner: `chown -R www-data:www-data storage/app/uploads`
- [ ] .env: Set correct `APP_URL`
- [ ] Route: Endpoint `/api/desa/produk-hukum/{id}/download` accessible

### Frontend
- [ ] .env: Set correct `VITE_API_URL`
- [ ] Build: `npm run build`
- [ ] Deploy: Upload dist folder
- [ ] Test: Buka detail page dan pastikan PDF tampil

## 📞 Support

Jika masih ada issue:

1. Check Laravel log: `storage/logs/laravel.log`
2. Check browser console: F12 > Network tab
3. Test endpoint langsung: `curl http://localhost:8000/api/desa/produk-hukum/{id}/download`
4. Verify file exists: `ls storage/app/uploads/produk_hukum/`

---

**Last Updated**: October 27, 2025
**Author**: Backend & Frontend Team
