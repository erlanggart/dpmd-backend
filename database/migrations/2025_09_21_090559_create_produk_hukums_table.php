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
        Schema::create('produk_hukums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('desa_id')->constrained('desas')->onDelete('cascade');
            $table->string('tipe_dokumen')->default('Peraturan Perundang-undangan');
            $table->string('judul');
            $table->string('nomor');
            $table->year('tahun');
            $table->enum('jenis', ['Peraturan Desa', 'Peraturan Kepala Desa', 'Keputusan Kepala Desa']);
            $table->enum('singkatan_jenis', ['PERDES', 'PERKADES', 'SK KADES']);
            $table->string('tempat_penetapan');
            $table->date('tanggal_penetapan');
            $table->string('sumber')->nullable();
            $table->string('subjek')->nullable();
            $table->enum('status_peraturan', ['berlaku', 'dicabut'])->default('berlaku');
            $table->string('keterangan_status')->nullable();
            $table->string('bahasa')->default('Indonesia');
            $table->string('bidang_hukum')->default('Tata Negara');
            $table->string('file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_hukums');
    }
};
