@echo off
echo === SETUP PERJADIN DATABASE ===
echo.

cd /d "c:\laragon\www\dpmd\dpmd-backend"

echo 1. Running migrations...
php artisan migrate --force
echo.

echo 2. Running BidangPerjadinSeeder...
php artisan db:seed --class=BidangPerjadinSeeder --force
echo.

echo 3. Running PersonilSeeder...  
php artisan db:seed --class=PersonilSeeder --force
echo.

echo 4. Testing endpoints...
php test_perjadin_endpoints.php
echo.

echo === SETUP COMPLETED ===
pause
