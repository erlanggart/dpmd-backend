# PERJADIN TROUBLESHOOTING GUIDE

## Problem Summary
- 401 Unauthorized errors on perjadin endpoints ✅ FIXED
- 404 Not Found for /api/personil-by-bidang/{id} ❌ NEEDS FIXING  
- Empty bidang dropdown in KegiatanForm ❌ NEEDS FIXING

## Root Cause Analysis

### 1. Frontend Issues ✅ FIXED
- KegiatanForm.jsx and KegiatanList.jsx were using direct axios calls without authentication
- Fixed by replacing `axios.get()` with `api.get()` to include Bearer tokens

### 2. Backend Route Mismatch ⚠️ IDENTIFIED
Frontend calls: `/api/personil-by-bidang/{id}`
Backend route: `/api/personil/{bidang_id}`

**Solution**: Routes are actually correct in api.php:
- `/api/bidang` → PerjadinBidangController::index
- `/api/personil/{bidang_id}` → PerjadinPersonilController::getByBidang

### 3. Database Issues ❌ NEEDS FIXING
- Tables may not exist: `bidangs`, `personil`
- Tables may be empty (no seeded data)
- Migration/seeding may not have run

## Files Status

### ✅ Frontend Files (FIXED)
- `dpmd-frontend/src/pages/perjadin/KegiatanForm.jsx` - Using api instance
- `dpmd-frontend/src/pages/perjadin/KegiatanList.jsx` - Using api instance

### ✅ Backend Controllers (VERIFIED)
- `app/Http/Controllers/Api/Perjadin/BidangController.php` - Returns bidangs
- `app/Http/Controllers/Api/Perjadin/PersonilController.php` - Returns personil by bidang

### ✅ Models (VERIFIED)
- `app/Models/Bidang.php` - Basic model with users relationship
- `app/Models/Personil.php` - Model with bidang relationship

### ✅ Database Structure (VERIFIED)
- `database/migrations/2025_09_06_092910_create_bidangs_table.php`
- `database/migrations/2025_09_23_100000_create_personils_table.php`

### ✅ Seeders (VERIFIED)
- `database/seeders/BidangPerjadinSeeder.php` - 8 bidangs
- `database/seeders/PersonilSeeder.php` - 100 personil mapped to 8 bidangs

### ✅ Routes (VERIFIED)
- `routes/api.php` - Correct route definitions

## Solution Steps

### Step 1: Database Setup
Run these commands in dpmd-backend directory:

```bash
# Run migrations
php artisan migrate

# Seed bidangs table  
php artisan db:seed --class=BidangPerjadinSeeder

# Seed personil table
php artisan db:seed --class=PersonilSeeder
```

Alternative: Run the batch file `setup_perjadin.bat`

### Step 2: Verify Database
Run test script:
```bash
php test_perjadin_endpoints.php
```

### Step 3: Test API Directly
Run direct test:
```bash
php test_api_direct.php
```

### Step 4: Test in Browser
With authentication headers:
- GET http://localhost/dpmd/dpmd-backend/public/api/bidang
- GET http://localhost/dpmd/dpmd-backend/public/api/personil/1

## Expected Data Structure

### Bidangs Table
| id | nama |
|----|------|
| 1 | Sekretariat |
| 2 | Sarana Prasarana Kewilayahan dan Ekonomi Desa |
| 3 | Kekayaan dan Keuangan Desa |
| 4 | Pemberdayaan Masyarakat Desa |
| 5 | Pemerintahan Desa |
| 6 | Tenaga Alih Daya |
| 7 | Tenaga Keamanan |
| 8 | Tenaga Kebersihan |

### Personil Table Structure
| id_personil | id_bidang | nama_personil |
|-------------|-----------|---------------|
| 1 | 1 | Drs. HADIJANA S.Sos. M.Si |
| 2 | 1 | ENDANG HARI MULYADINATA S.Kom |
| ... | ... | ... |

## Verification Checklist

- [ ] Tables `bidangs` and `personil` exist
- [ ] Tables contain data (8 bidangs, ~100 personil)
- [ ] API endpoints return data without authentication errors
- [ ] Frontend dropdown populates with bidang data
- [ ] Personil dropdown updates when bidang is selected
- [ ] Form submission works without errors

## Common Issues & Solutions

### Issue: "Table doesn't exist"
Solution: Run `php artisan migrate`

### Issue: "No data returned"  
Solution: Run seeders with `--class=BidangPerjadinSeeder` and `--class=PersonilSeeder`

### Issue: "401 Unauthorized"
Solution: Ensure frontend uses `api` instance from `src/api.js`, not direct axios

### Issue: "404 Not Found"
Solution: Check route exists in `routes/api.php` and controller methods exist

### Issue: Foreign key constraint error
Solution: Ensure bidangs table is seeded before personil table
