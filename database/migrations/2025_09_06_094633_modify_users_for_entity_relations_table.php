<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom lama jika masih ada di migrasi ini
            // Jika Anda sudah membuat migrasi terpisah untuk menghapusnya, Anda bisa hapus baris ini
            // $table->dropColumn('kode_wilayah');

            // Tambahkan foreign key baru
            $table->foreignId('desa_id')->nullable()->constrained('desas');
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatans');
            $table->foreignId('bidang_id')->nullable()->constrained('bidangs');
            $table->foreignId('dinas_id')->nullable()->constrained('dinas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key untuk proses rollback
            $table->dropForeign(['desa_id']);
            $table->dropForeign(['kecamatan_id']);
            $table->dropForeign(['bidang_id']);
            $table->dropForeign(['dinas_id']);

            $table->dropColumn(['desa_id', 'kecamatan_id', 'bidang_id', 'dinas_id']);
        });
    }
};
