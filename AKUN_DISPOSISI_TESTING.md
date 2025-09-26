# Akun Testing untuk Sistem Disposisi Persuratan

## Daftar Akun yang Telah Dibuat

### 1. Staff Sekretariat (Input Surat Masuk) 
- **Email**: `staff.sekretariat@dpmd.com`
- **Password**: `password`
- **Role**: `staff`
- **Function**: Input surat masuk ke sistem
- **Access**: `/dashboard/disposisi-persuratan`

### 2. Kepala Dinas (Review & Disposisi)
- **Email**: `kepala.dinas@dpmd.com`
- **Password**: `password`
- **Role**: `kepala_dinas`
- **Function**: Review surat masuk dan membuat disposisi
- **Access**: `/dashboard/disposisi/kepala-dinas`

### 3. Sekretaris Dinas (Kelola Disposisi)
- **Email**: `sekretaris.dinas@dpmd.com`
- **Password**: `password`
- **Role**: `sekretaris_dinas`
- **Function**: Meneruskan disposisi dari Kepala Dinas ke bidang terkait
- **Access**: `/dashboard/disposisi/sekretaris-dinas`

### 4. Kepala Bidang Pemerintahan
- **Email**: `kepala.pemerintahan@dpmd.com`
- **Password**: `password`
- **Role**: `kepala_bidang_pemerintahan`
- **Function**: Menerima dan melaporkan disposisi bidang pemerintahan
- **Access**: `/dashboard/disposisi/kepala-bidang`

### 5. Kepala Bidang Kesejahteraan Rakyat
- **Email**: `kepala.kesra@dpmd.com`
- **Password**: `password`
- **Role**: `kepala_bidang_kesra`
- **Function**: Menerima dan melaporkan disposisi bidang kesra
- **Access**: `/dashboard/disposisi/kepala-bidang`

### 6. Kepala Bidang Ekonomi
- **Email**: `kepala.ekonomi@dpmd.com`
- **Password**: `password`
- **Role**: `kepala_bidang_ekonomi`
- **Function**: Menerima dan melaporkan disposisi bidang ekonomi
- **Access**: `/dashboard/disposisi/kepala-bidang`

### 7. Kepala Bidang Fisik dan Prasarana
- **Email**: `kepala.fisik@dpmd.com`
- **Password**: `password`
- **Role**: `kepala_bidang_fisik`
- **Function**: Menerima dan melaporkan disposisi bidang fisik
- **Access**: `/dashboard/disposisi/kepala-bidang`

## Cara Testing

### 1. Login dengan Role Staff
1. Login dengan `staff.sekretariat@dpmd.com` / `password`
2. Akses menu "Disposisi Persuratan"
3. Input surat masuk baru
4. Surat akan muncul untuk direview oleh Kepala Dinas

### 2. Login dengan Role Kepala Dinas
1. Login dengan `kepala.dinas@dpmd.com` / `password`
2. Menu otomatis mengarah ke "Disposisi - Kepala Dinas"
3. Review surat dari staff
4. Buat disposisi ke Sekretaris atau langsung ke bidang

### 3. Login dengan Role Sekretaris Dinas
1. Login dengan `sekretaris.dinas@dpmd.com` / `password`
2. Menu otomatis mengarah ke "Disposisi - Sekretaris"
3. Lihat disposisi dari Kepala Dinas
4. Teruskan ke bidang terkait atau tandai selesai

### 4. Login dengan Role Kepala Bidang
1. Login dengan salah satu akun kepala bidang
2. Menu otomatis mengarah ke "Disposisi - Bid. [Nama Bidang]"
3. Lihat disposisi yang masuk
4. Buat laporan penyelesaian

## Alur Kerja Disposisi

```
Staff Sekretariat → Input Surat Masuk
                    ↓
Kepala Dinas → Review & Buat Disposisi
                    ↓
Sekretaris Dinas → Teruskan ke Bidang (atau selesai)
                    ↓
Kepala Bidang → Proses & Buat Laporan
```

## Fitur Testing

### Role Switcher (Developer Mode)
- Tersedia di bottom-right corner saat development
- Bisa switch role tanpa logout
- Halaman akan reload otomatis dengan role baru

### Auto Redirect
- Setiap role otomatis diarahkan ke dashboard yang sesuai
- Menu navigasi adaptif berdasarkan role
- Protection route dengan RoleGuard

## Database Command

Untuk re-run seeder jika diperlukan:
```bash
php artisan db:seed --class=DisposisiUserSeeder
```

Untuk melihat semua user disposisi:
```bash
php artisan tinker
User::whereIn('role', ['staff', 'kepala_dinas', 'sekretaris_dinas', 'kepala_bidang_pemerintahan', 'kepala_bidang_kesra', 'kepala_bidang_ekonomi', 'kepala_bidang_fisik'])->get(['name', 'email', 'role']);
```

## Notes

- Admin Sekretariat yang sudah ada (`sekretariat@dpmd.com`) juga bisa mengakses input surat masuk
- Semua password menggunakan `password` untuk kemudahan testing
- Role-based access control sudah terintegrasi dengan sistem routing