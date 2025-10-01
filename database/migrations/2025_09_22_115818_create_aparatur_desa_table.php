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
        Schema::create('aparatur_desa', function (Blueprint $table) {
            // Use UUID as primary key
            $table->uuid('id')->primary();
            $table->foreignId('desa_id')->constrained('desas')->onDelete('cascade');

            // --- Biodata ---
            $table->string('nama_lengkap');
            $table->string('jabatan');
            $table->string('nipd')->nullable()->comment('Nomor Induk Perangkat Desa');
            $table->string('niap')->nullable()->comment('Nomor Induk Aparatur Pemerintah');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('pendidikan_terakhir');
            $table->string('agama');
            $table->string('pangkat_golongan')->nullable();
            $table->date('tanggal_pengangkatan');
            $table->string('nomor_sk_pengangkatan');
            $table->date('tanggal_pemberhentian')->nullable();
            $table->string('nomor_sk_pemberhentian')->nullable();
            $table->string('keterangan')->nullable();
            $table->enum('status', ['Aktif', 'Tidak Aktif'])->default('Aktif');

            // --- Data Terhubung ---
            $table->foreignUuid('produk_hukum_id')->nullable()->constrained('produk_hukums')->nullOnDelete()->comment('SK Pengangkatan dari Produk Hukum');
            $table->string('bpjs_kesehatan_nomor')->nullable();
            $table->string('bpjs_ketenagakerjaan_nomor')->nullable();
            $table->string('file_bpjs_kesehatan')->nullable();
            $table->string('file_bpjs_ketenagakerjaan')->nullable();

            // --- File Lampiran ---
            $table->string('file_pas_foto')->nullable();
            $table->string('file_ktp')->nullable();
            $table->string('file_kk')->nullable();
            $table->string('file_akta_kelahiran')->nullable();
            $table->string('file_ijazah_terakhir')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aparatur_desa');
    }
};
