<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('petugas_monitoring', function (Blueprint $table) {
            $table->id();
            $table->string('nama_desa');
            $table->string('nama_kecamatan');
            $table->string('nama_petugas');
            $table->unsignedBigInteger('desa_id')->nullable();
            $table->unsignedBigInteger('kecamatan_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('desa_id')->references('id')->on('desas')->onDelete('set null');
            $table->foreign('kecamatan_id')->references('id')->on('kecamatans')->onDelete('set null');
            $table->index(['desa_id', 'kecamatan_id']);
        });
        
        // Insert data petugas monitoring dari screenshot
        DB::table('petugas_monitoring')->insert([
            ['nama_desa' => 'Cipayung Girang', 'nama_kecamatan' => 'Megamendung', 'nama_petugas' => 'Drs. HADIJANA,S.Sos.,M.Si'],
            ['nama_desa' => 'Cariu', 'nama_kecamatan' => 'Cariu', 'nama_petugas' => 'HARIF WAHYUDI, S.Kom'],
            ['nama_desa' => 'Bantarkuning', 'nama_kecamatan' => 'Cariu', 'nama_petugas' => 'CAHYO BUDHIARTO, S. AP'],
            ['nama_desa' => 'Cihideung Udik', 'nama_kecamatan' => 'Ciampea', 'nama_petugas' => 'UJANG MUHAROM'],
            ['nama_desa' => 'Cimanggu I', 'nama_kecamatan' => 'Cibungbulang', 'nama_petugas' => 'A R I S, SE'],
            ['nama_desa' => 'Galuga', 'nama_kecamatan' => 'Cibungbulang', 'nama_petugas' => 'Muhamad Rafli, S.E.'],
            ['nama_desa' => 'Tugujaya', 'nama_kecamatan' => 'Cigombong', 'nama_petugas' => 'MIMAH MAHDIAH, S.E., M.M'],
            ['nama_desa' => 'Ciburuy', 'nama_kecamatan' => 'Cigombong', 'nama_petugas' => 'SITI SARAH FATMAWATI, S. STP., M.IP'],
            ['nama_desa' => 'Ciburayut', 'nama_kecamatan' => 'Cigombong', 'nama_petugas' => 'FATUR ARI SETYANTO, S. STP'],
            ['nama_desa' => 'Ciadeg', 'nama_kecamatan' => 'Cigombong', 'nama_petugas' => 'R. L U K M A N , S.E., M.A'],
            ['nama_desa' => 'Cigombong', 'nama_kecamatan' => 'Cigombong', 'nama_petugas' => 'Muhammad Fahmi S.KOM'],
            ['nama_desa' => 'Watesjaya', 'nama_kecamatan' => 'Cigombong', 'nama_petugas' => 'H. ACHMAD HADIYATUL M., S.Sos. MM'],
            ['nama_desa' => 'Tajurhalang', 'nama_kecamatan' => 'Cijeruk', 'nama_petugas' => 'SITI NURJANA ADAM, S.Sos'],
            ['nama_desa' => 'Cijeruk', 'nama_kecamatan' => 'Cijeruk', 'nama_petugas' => 'RANI SITI NUR AINI, S.IP, M.Si'],
            ['nama_desa' => 'Pasirangin', 'nama_kecamatan' => 'Cileungsi', 'nama_petugas' => 'Denna S. Dahmar Hidayati, S. Ak'],
            ['nama_desa' => 'Limusunggal', 'nama_kecamatan' => 'Cileungsi', 'nama_petugas' => 'YADI SUPRIADI'],
            ['nama_desa' => 'Gandoang', 'nama_kecamatan' => 'Cileungsi', 'nama_petugas' => 'SRI PURWANINGSIH, S.E.'],
            ['nama_desa' => 'Cipeucang', 'nama_kecamatan' => 'Cileungsi', 'nama_petugas' => 'JUANGSIH ARYANI, S.IP, M. IP'],
            ['nama_desa' => 'Jatisari', 'nama_kecamatan' => 'Cileungsi', 'nama_petugas' => 'IRMAWATI SARI., S.E.'],
            ['nama_desa' => 'Situsari', 'nama_kecamatan' => 'Cileungsi', 'nama_petugas' => 'TRI SUSANTI, S. Ak.'],
            ['nama_desa' => 'Mekarsari', 'nama_kecamatan' => 'Cileungsi', 'nama_petugas' => 'MIRA DEWI SITANGGANG, S.E., M.M.'],
            ['nama_desa' => 'Kota Batu', 'nama_kecamatan' => 'Ciomas', 'nama_petugas' => 'MEIKE HERAWATI, S. E.'],
            ['nama_desa' => 'Kopo', 'nama_kecamatan' => 'Cisarua', 'nama_petugas' => 'ALI NASRULLAH, S.H.'],
            ['nama_desa' => 'Citeko', 'nama_kecamatan' => 'Cisarua', 'nama_petugas' => 'MOCH. MAHPUDIN, S.H. M.Si'],
            ['nama_desa' => 'Cilember', 'nama_kecamatan' => 'Cisarua', 'nama_petugas' => 'FHADLI RUKMANA., AK'],
            ['nama_desa' => 'Jogjogan', 'nama_kecamatan' => 'Cisarua', 'nama_petugas' => 'MOCH IHSAN MAULANA SA\'BAN, S.E.'],
            ['nama_desa' => 'Kalongsawah', 'nama_kecamatan' => 'Jasinga', 'nama_petugas' => 'N U R D I N, S. AP'],
            ['nama_desa' => 'Sipak', 'nama_kecamatan' => 'Jasinga', 'nama_petugas' => 'DADANG TEGUH NURYULISTIWA, S.H'],
            ['nama_desa' => 'Setu', 'nama_kecamatan' => 'Jasinga', 'nama_petugas' => 'MAINY, SE. MM'],
            ['nama_desa' => 'Jasinga', 'nama_kecamatan' => 'Jasinga', 'nama_petugas' => 'SITI MARIYAM., S. AP.'],
            ['nama_desa' => 'Lulut', 'nama_kecamatan' => 'Klapanunggal', 'nama_petugas' => 'WAWAN SETIAWAN'],
            ['nama_desa' => 'Karacak', 'nama_kecamatan' => 'Leuwiliang', 'nama_petugas' => 'Imam Septiyansyah, S. Kom'],
            ['nama_desa' => 'Leuwiliang', 'nama_kecamatan' => 'Leuwiliang', 'nama_petugas' => 'Zaenudin, S. Pd'],
            ['nama_desa' => 'Ciasmara', 'nama_kecamatan' => 'Pamijahan', 'nama_petugas' => 'S U H A D A, S.E.'],
            ['nama_desa' => 'Lumpang', 'nama_kecamatan' => 'Parung Panjang', 'nama_petugas' => 'Fahri Setya Gunawan'],
            ['nama_desa' => 'Parung Panjang', 'nama_kecamatan' => 'Parung Panjang', 'nama_petugas' => 'Dian Munandar'],
            ['nama_desa' => 'Bantarjaya', 'nama_kecamatan' => 'Rancabungur', 'nama_petugas' => 'ENDANG HARI MULYADINATA, S. Kom']
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petugas_monitoring');
    }
};
