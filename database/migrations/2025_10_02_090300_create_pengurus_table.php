<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengurus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('desa_id')->constrained('desas')->onDelete('cascade');
            // Polymorphic relation to kelembagaan tables
            $table->uuidMorphs('pengurusable'); // pengurusable_id (uuid), pengurusable_type (string)

            // Jabatan + periode
            $table->string('jabatan'); // Ketua, Sekretaris, Bendahara, dsb.
            $table->date('tanggal_mulai_jabatan')->nullable();
            $table->date('tanggal_akhir_jabatan')->nullable();
            $table->enum('status_jabatan', ['aktif', 'selesai'])->default('aktif');

            // Status verifikasi data
            $table->enum('status_verifikasi', ['verified', 'unverified'])->default('unverified');

            // Referensi SK Pengangkatan (Produk Hukum desa)
            $table->foreignUuid('produk_hukum_id')->nullable()->constrained('produk_hukums')->nullOnDelete();

            // Data personal pengurus
            $table->string('nama_lengkap');
            $table->string('nik', 32)->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->string('status_perkawinan')->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 32)->nullable();
            $table->string('pendidikan')->nullable();

            // File attachments (store filenames only)
            $table->string('avatar')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengurus');
    }
};
