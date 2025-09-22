<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\User;

echo "Users with bidang roles:\n";
echo "========================\n";

$users = User::whereIn('role', ['sekretariat', 'sarana_prasarana', 'kekayaan_keuangan', 'pemberdayaan_masyarakat', 'pemerintahan_desa'])->get(['name', 'email', 'role']);

foreach($users as $user) {
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Role: " . $user->role . "\n";
    echo "---\n";
}

echo "\nSuper admin users:\n";
echo "==================\n";

$superAdmins = User::where('role', 'superadmin')->orWhereHas('roles', function($query) {
    $query->where('name', 'superadmin');
})->get(['name', 'email', 'role']);

foreach($superAdmins as $user) {
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Role: " . $user->role . "\n";
    echo "---\n";
}