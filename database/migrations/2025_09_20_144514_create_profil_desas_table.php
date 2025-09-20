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
        Schema::create('profil_desas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->unique()->constrained('desas')->onDelete('cascade');
            $table->string('klasifikasi_desa')->nullable();
            $table->string('status_desa')->nullable();
            $table->string('tipologi_desa')->nullable();
            $table->integer('jumlah_penduduk')->nullable();
            $table->text('sejarah_desa')->nullable();
            $table->text('demografi')->nullable();
            $table->text('potensi_desa')->nullable();
            $table->string('no_telp')->nullable();
            $table->string('email')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('luas_wilayah')->nullable();
            $table->text('alamat_kantor')->nullable();
            $table->string('radius_ke_kecamatan')->nullable();
            $table->string('foto_kantor_desa_path')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_desas');
    }
};
