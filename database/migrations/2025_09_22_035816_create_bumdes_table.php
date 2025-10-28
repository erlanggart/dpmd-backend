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
        Schema::create('bumdes', function (Blueprint $table) {
            $table->id();
            $table->string('kode_desa')->unique();
            $table->string('kecamatan');
            $table->string('desa');
            $table->string('namabumdesa');
            $table->string('status')->default('aktif');
            $table->text('keterangan_tidak_aktif')->nullable();
            $table->string('NIB')->nullable();
            $table->string('LKPP')->nullable();
            $table->string('NPWP')->nullable();
            $table->string('badanhukum')->nullable();
            
            // Pengurus
            $table->string('NamaPenasihat')->nullable();
            $table->string('JenisKelaminPenasihat')->nullable();
            $table->string('HPPenasihat')->nullable();
            $table->string('NamaPengawas')->nullable();
            $table->string('JenisKelaminPengawas')->nullable();
            $table->string('HPPengawas')->nullable();
            $table->string('NamaDirektur')->nullable();
            $table->string('JenisKelaminDirektur')->nullable();
            $table->string('HPDirektur')->nullable();
            $table->string('NamaSekretaris')->nullable();
            $table->string('JenisKelaminSekretaris')->nullable();
            $table->string('HPSekretaris')->nullable();
            $table->string('NamaBendahara')->nullable();
            $table->string('JenisKelaminBendahara')->nullable();
            $table->string('HPBendahara')->nullable();
            
            // Organisasi
            $table->string('TahunPendirian')->nullable();
            $table->text('AlamatBumdesa')->nullable();
            $table->string('Alamatemail')->nullable();
            $table->integer('TotalTenagaKerja')->nullable();
            $table->string('TelfonBumdes')->nullable();
            
            // Usaha
            $table->text('JenisUsaha')->nullable();
            $table->string('JenisUsahaUtama')->nullable();
            $table->text('JenisUsahaLainnya')->nullable();
            $table->decimal('Omset2023', 15, 2)->nullable();
            $table->decimal('Laba2023', 15, 2)->nullable();
            $table->decimal('Omset2024', 15, 2)->nullable();
            $table->decimal('Laba2024', 15, 2)->nullable();
            
            // Permodalan
            $table->decimal('PenyertaanModal2019', 15, 2)->nullable();
            $table->decimal('PenyertaanModal2020', 15, 2)->nullable();
            $table->decimal('PenyertaanModal2021', 15, 2)->nullable();
            $table->decimal('PenyertaanModal2022', 15, 2)->nullable();
            $table->decimal('PenyertaanModal2023', 15, 2)->nullable();
            $table->decimal('PenyertaanModal2024', 15, 2)->nullable();
            $table->decimal('SumberLain', 15, 2)->nullable();
            $table->string('JenisAset')->nullable();
            $table->decimal('NilaiAset', 15, 2)->nullable();
            
            // Kemitraan
            $table->text('KerjasamaPihakKetiga')->nullable();
            $table->string('TahunMulai-TahunBerakhir')->nullable();
            
            // Kontribusi PADes
            $table->decimal('KontribusiTerhadapPADes2021', 15, 2)->nullable();
            $table->decimal('KontribusiTerhadapPADes2022', 15, 2)->nullable();
            $table->decimal('KontribusiTerhadapPADes2023', 15, 2)->nullable();
            $table->decimal('KontribusiTerhadapPADes2024', 15, 2)->nullable();
            
            // Peran & Bantuan
            $table->string('Ketapang2024')->nullable();
            $table->string('Ketapang2025')->nullable();
            $table->text('BantuanKementrian')->nullable();
            $table->text('BantuanLaptopShopee')->nullable();
            $table->string('NomorPerdes')->nullable();
            $table->string('DesaWisata')->nullable();
            
            // File uploads
            $table->string('LaporanKeuangan2021')->nullable();
            $table->string('LaporanKeuangan2022')->nullable();
            $table->string('LaporanKeuangan2023')->nullable();
            $table->string('LaporanKeuangan2024')->nullable();
            $table->string('Perdes')->nullable();
            $table->string('ProfilBUMDesa')->nullable();
            $table->string('BeritaAcara')->nullable();
            $table->string('AnggaranDasar')->nullable();
            $table->string('AnggaranRumahTangga')->nullable();
            $table->string('ProgramKerja')->nullable();
            $table->string('SK_BUM_Desa')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bumdes');
    }
};
