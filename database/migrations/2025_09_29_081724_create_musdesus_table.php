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
        Schema::create('musdesus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->string('nama_file_asli');
            $table->string('path_file');
            $table->string('mime_type');
            $table->bigInteger('ukuran_file'); // dalam bytes
            $table->string('nama_pengupload');
            $table->string('email_pengupload')->nullable();
            $table->string('telepon_pengupload')->nullable();
            $table->foreignId('desa_id')->constrained('desas')->onDelete('cascade');
            $table->foreignId('kecamatan_id')->constrained('kecamatans')->onDelete('cascade');
            $table->text('keterangan')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->timestamp('tanggal_musdesus')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('musdesus');
    }
};
