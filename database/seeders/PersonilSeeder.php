<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Bidang; // Pastikan model Bidang sudah diimport

class PersonilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Nonaktifkan foreign key check sementara agar truncate bisa berjalan
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Bersihkan tabel personil terlebih dahulu untuk menghindari duplikasi
        // Nama tabel sekarang adalah 'personil'
        DB::table('personil')->truncate();

        // Aktifkan kembali foreign key check
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Mapping nama bidang ke ID, lebih efisien
        $bidangIds = Bidang::pluck('id', 'nama')->toArray();

        // Data personil
        $personilData = [
            // Sekretariat
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'Drs. HADIJANA S.Sos. M.Si'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'ENDANG HARI MULYADINATA S.Kom'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'LISNA SUSANTI S. E.'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'LEA HARUNDARI GESIT PERDANA PUTRI S.Sos'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'IRFAN'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'MEIKE HERAWATI S. E.'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'TRI WIDIYARTO S. IP, M. PA'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'ARIS SE'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'SOLEHUDIN ALAYUBI'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'LUSIANA DEWI S.Sos'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'MURYATI'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'FIRMAN'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'VIVI ERNAWATI A. Md'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'JUANGSIH ARYANI S.IP, M. IP'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'FERDI SERDIANA S.E. M. Si'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'TRI SUSANTI S. Ak.'],
            ['id_bidang' => $bidangIds['Sekretariat'], 'nama_personil' => 'RIKA FRANSISKA B.M. S.E.'],

            // Pemberdayaan Masyarakat Desa (PMD)
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'RANI SITI NUR AINI S.IP M.Si'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'SITI NURJANA ADAM S.Sos'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'CAHYO BUDHIARTO S. AP'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'FENI APRIANI S. Sos'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'NABILA FITRIANA PRATIWI'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'NURDIN S. AP'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'DADANG TEGUH NURYULISTIWA S.H'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'MAINY SE. MM'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'SITI MARIYAM S. AP.'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'AISYAH ARGIANTI S.Sos'],
            ['id_bidang' => $bidangIds['Pemberdayaan Masyarakat Desa'], 'nama_personil' => 'RAHMAT IGO WIBISONO S.Tr. I.P.'],

            // kekayaan dan keuangan desa
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'ALI NASRULLAH S.H.'],
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'SELO ENDARTI'],
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'LINA PARLINA A. Md'],
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'FHADLI RUKMANA S. AK'],
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'MOCH IHSAN MAULANA SA\'BAN, S.E.'],
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'ASTI NURPADILAH S.E.'],
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'DIMAS EKO NUGROHO S.I.Kom'],
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'MOCH. MAHPUDIN S.H. M.Si'],
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'SUHADA S.E.'],
            ['id_bidang' => $bidangIds['Kekayaan dan Keuangan Desa'], 'nama_personil' => 'CARISSA AZARINE S.Psi'],

            // pemerintahan desa
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'FEBRIYANTI S.STP. M.Si'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'H. ACHMAD HADIYATUL M S.Sos. MM'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'R. LUKMAN S.E. M.A'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'FATUR ARI SETYANTO S. STP'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'WAWAN SETIAWAN'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'UJANG MUHAROM'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'DESY ARTA ROSARI SITANGGANG S.Tr.Sos'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'MIMAH MAHDIAH S.E. M.M'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'SITI SARAH FATMAWATI S. STP. M.IP'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'AYU WANDIRA S.E.'],
            ['id_bidang' => $bidangIds['Pemerintahan Desa'], 'nama_personil' => 'DHAMARA NURDIANSYAH'],
            
            // Sarana Prasarana Kewilayahan dan Ekonomi Desa (SPKED)
            ['id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'], 'nama_personil' => 'MIRA DEWI SITANGGANG S.E. M.M.'],
            ['id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'], 'nama_personil' => 'HARIF WAHYUDI S.Kom'],
            ['id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'], 'nama_personil' => 'IRMAWATI SARI S.E.'],
            ['id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'], 'nama_personil' => 'YADI SUPRIADI'],
            ['id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'], 'nama_personil' => 'AYULIA NUR RACHMAWATI S.Sos'],
            ['id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'], 'nama_personil' => 'DARUL TAUFIQ'],
            ['id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'], 'nama_personil' => 'ALAN RIADI S.E. M. Si'],
            ['id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'], 'nama_personil' => 'SRI PURWANINGSIH S.E.'],
            ['id_bidang' => $bidangIds['Sarana Prasarana Kewilayahan dan Ekonomi Desa'], 'nama_personil' => 'SITI RAHMAH S.E.'],
            
            // Tenaga keamanan
            ['id_bidang' => $bidangIds['Tenaga Keamanan'], 'nama_personil' => 'Wawan Darmawan'],
            ['id_bidang' => $bidangIds['Tenaga Keamanan'], 'nama_personil' => 'Suratman'],
            ['id_bidang' => $bidangIds['Tenaga Keamanan'], 'nama_personil' => 'Adi Hermawan'],
            ['id_bidang' => $bidangIds['Tenaga Keamanan'], 'nama_personil' => 'Otasi Carles Manalu'],
            ['id_bidang' => $bidangIds['Tenaga Keamanan'], 'nama_personil' => 'Ahmad Imam Maulana'],
            ['id_bidang' => $bidangIds['Tenaga Keamanan'], 'nama_personil' => 'Wahyu Ari Sucipto'],
            ['id_bidang' => $bidangIds['Tenaga Keamanan'], 'nama_personil' => 'Atika Seknun'],
            ['id_bidang' => $bidangIds['Tenaga Keamanan'], 'nama_personil' => 'Iyah Samsiyah'],

            // Tenaga Kebersihan
            ['id_bidang' => $bidangIds['Tenaga Kebersihan'], 'nama_personil' => 'SUKARMIN'],
            ['id_bidang' => $bidangIds['Tenaga Kebersihan'], 'nama_personil' => 'Rini'],
            ['id_bidang' => $bidangIds['Tenaga Kebersihan'], 'nama_personil' => 'Erwin Yuniawan Kusuma'],
            ['id_bidang' => $bidangIds['Tenaga Kebersihan'], 'nama_personil' => 'Umar'],
            ['id_bidang' => $bidangIds['Tenaga Kebersihan'], 'nama_personil' => 'Haerudin'],
            ['id_bidang' => $bidangIds['Tenaga Kebersihan'], 'nama_personil' => 'Rizkia Safitri'],
            ['id_bidang' => $bidangIds['Tenaga Kebersihan'], 'nama_personil' => 'Syukur Makmun'],
            ['id_bidang' => $bidangIds['Tenaga Kebersihan'], 'nama_personil' => 'Beni Permana'],
            ['id_bidang' => $bidangIds['Tenaga Kebersihan'], 'nama_personil' => 'Basri Ramadan'],

            // Tenaga alih daya
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Dian Munandar'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Rachmat Ramadhan, S. Ak'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Arfa Cesaria, A. Md'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Nina Melinda, S.Pd'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Mas Cecep Tino Noviandi, A. Md'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Chintia Ainun Fadhilah, S.M.'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Rizki Iriani, A. Md'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Fariz Andifa, S.Kom'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Imam Septiyansyah, S. Kom'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Erma Diah Vitaloka, S.T.'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Zaenudin, S. Pd'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Neneng Muflihah, S.E'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Dian Noviani, S. IK'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Gema Alifa Eastiana , S.I.Kom'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Muhamad Rafli, S.E.'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Aulya Ardhi Fauqa, S.H.'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Sandra Febri Ramdhiani, S.H.'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Muhammad Fahmi, S. Kom'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Moch. Rizki Utama , S.P.W.K'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Denna S. Dahmar Hidayati, S. Ak'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Fahri Setya Gunawan'],
            ['id_bidang' => $bidangIds['Tenaga Alih Daya'], 'nama_personil' => 'Devin Prasetia Bastian, S.P.W.K'],
            
 
 

 

 
 

 













        ];

        DB::table('personil')->insert($personilData);
    }
}