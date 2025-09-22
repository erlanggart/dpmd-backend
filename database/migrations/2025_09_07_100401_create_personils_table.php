<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */



    public function run()
    {
        // Bersihkan tabel personil terlebih dahulu untuk menghindari duplikasi
        DB::table('personil')->truncate();

        DB::table('personil')->insert([
            ['id_personil' => 1, 'id_bidang' => 1, 'nama_personil' => 'Drs. HADIJANA S.Sos. M.Si'],
            ['id_personil' => 2, 'id_bidang' => 1, 'nama_personil' => 'ENDANG HARI MULYADINATA S.Kom'],
            ['id_personil' => 3, 'id_bidang' => 1, 'nama_personil' => 'LISNA SUSANTI S. E.'],
            ['id_personil' => 4, 'id_bidang' => 1, 'nama_personil' => 'LEA HARUNDARI GESIT PERDANA PUTRI S.Sos'],
            ['id_personil' => 5, 'id_bidang' => 1, 'nama_personil' => 'IRFAN'],
            ['id_personil' => 6, 'id_bidang' => 1, 'nama_personil' => 'MEIKE HERAWATI S. E.'],
            ['id_personil' => 6, 'id_bidang' => 1, 'nama_personil' => 'TRI WIDIYARTO S. IP, M. PA'],
            ['id_personil' => 8, 'id_bidang' => 1, 'nama_personil' => 'ARIS SE'],
            ['id_personil' => 9, 'id_bidang' => 1, 'nama_personil' => 'SOLEHUDIN ALAYUBI'],
            ['id_personil' => 10, 'id_bidang' => 1, 'nama_personil' => 'LUSIANA DEWI S.Sos'],
            ['id_personil' => 11, 'id_bidang' => 1, 'nama_personil' => 'MURYATI'],
            ['id_personil' => 12, 'id_bidang' => 1, 'nama_personil' => 'FIRMAN'],
            ['id_personil' => 13, 'id_bidang' => 1, 'nama_personil' => 'VIVI ERNAWATI A. Md'],
            ['id_personil' => 14, 'id_bidang' => 1, 'nama_personil' => 'JUANGSIH ARYANI S.IP, M. IP'],
            ['id_personil' => 15, 'id_bidang' => 1, 'nama_personil' => 'FERDI SERDIANA S.E. M. Si'],
            ['id_personil' => 16, 'id_bidang' => 1, 'nama_personil' => 'TRI SUSANTI S. Ak.'],
            ['id_personil' => 17, 'id_bidang' => 1, 'nama_personil' => 'RIKA FRANSISKA B.M. S.E.'],
            ['id_personil' => 18, 'id_bidang' => 3, 'nama_personil' => 'ALI NASRULLAH S.H.'],
            ['id_personil' => 19, 'id_bidang' => 3, 'nama_personil' => 'SELO ENDARTI'],
            ['id_personil' => 20, 'id_bidang' => 3, 'nama_personil' => 'LINA PARLINA A. Md'],
            ['id_personil' => 21, 'id_bidang' => 3, 'nama_personil' => 'FHADLI RUKMANA S. AK'],
            ['id_personil' => 22, 'id_bidang' => 3, 'nama_personil' => 'MOCH IHSAN MAULANA SA\'BAN, S.E.'],
            ['id_personil' => 23, 'id_bidang' => 3, 'nama_personil' => 'ASTI NURPADILAH S.E.'],
            ['id_personil' => 24, 'id_bidang' => 3, 'nama_personil' => 'DIMAS EKO NUGROHO S.I.Kom'],
            ['id_personil' => 25, 'id_bidang' => 3, 'nama_personil' => 'MOCH. MAHPUDIN S.H. M.Si'],
            ['id_personil' => 26, 'id_bidang' => 3, 'nama_personil' => 'SUHADA S.E.'],
            ['id_personil' => 27, 'id_bidang' => 3, 'nama_personil' => 'CARISSA AZARINE S.Psi'],
            ['id_personil' => 28, 'id_bidang' => 4, 'nama_personil' => 'RANI SITI NUR AINI S.IP M.Si'],
            ['id_personil' => 29, 'id_bidang' => 4, 'nama_personil' => 'SITI NURJANA ADAM S.Sos'],
            ['id_personil' => 30, 'id_bidang' => 4, 'nama_personil' => 'CAHYO BUDHIARTO S. AP'],
            ['id_personil' => 31, 'id_bidang' => 4, 'nama_personil' => 'FENI APRIANI S. Sos'],
            ['id_personil' => 32, 'id_bidang' => 4, 'nama_personil' => 'NABILA FITRIANA PRATIWI'],
            ['id_personil' => 33, 'id_bidang' => 4, 'nama_personil' => 'NURDIN S. AP'],
            ['id_personil' => 34, 'id_bidang' => 4, 'nama_personil' => 'DADANG TEGUH NURYULISTIWA S.H'],
            ['id_personil' => 35, 'id_bidang' => 4, 'nama_personil' => 'MAINY SE. MM'],
            ['id_personil' => 36, 'id_bidang' => 4, 'nama_personil' => 'SITI MARIYAM S. AP.'],
            ['id_personil' => 37, 'id_bidang' => 4, 'nama_personil' => 'AISYAH ARGIANTI S.Sos'],
            ['id_personil' => 38, 'id_bidang' => 4, 'nama_personil' => 'RAHMAT IGO WIBISONO S.Tr. I.P.'],
            ['id_personil' => 39, 'id_bidang' => 5, 'nama_personil' => 'FEBRIYANTI S.STP. M.Si'],
            ['id_personil' => 40, 'id_bidang' => 5, 'nama_personil' => 'H. ACHMAD HADIYATUL M S.Sos. MM'],
            ['id_personil' => 41, 'id_bidang' => 5, 'nama_personil' => 'R. LUKMAN S.E. M.A'],
            ['id_personil' => 42, 'id_bidang' => 5, 'nama_personil' => 'FATUR ARI SETYANTO S. STP'],
            ['id_personil' => 43, 'id_bidang' => 5, 'nama_personil' => 'WAWAN SETIAWAN'],
            ['id_personil' => 44, 'id_bidang' => 5, 'nama_personil' => 'UJANG MUHAROM'],
            ['id_personil' => 45, 'id_bidang' => 5, 'nama_personil' => 'DESY ARTA ROSARI SITANGGANG S.Tr.Sos'],
            ['id_personil' => 46, 'id_bidang' => 5, 'nama_personil' => 'MIMAH MAHDIAH S.E. M.M'],
            ['id_personil' => 47, 'id_bidang' => 5, 'nama_personil' => 'SITI SARAH FATMAWATI S. STP. M.IP'],
            ['id_personil' => 48, 'id_bidang' => 5, 'nama_personil' => 'AYU WANDIRA S.E.'],
            ['id_personil' => 49, 'id_bidang' => 5, 'nama_personil' => 'DHAMARA NURDIANSYAH'],
            ['id_personil' => 50, 'id_bidang' => 2, 'nama_personil' => 'MIRA DEWI SITANGGANG S.E. M.M.'],
            ['id_personil' => 51, 'id_bidang' => 2, 'nama_personil' => 'HARIF WAHYUDI S.Kom'],
            ['id_personil' => 52, 'id_bidang' => 2, 'nama_personil' => 'IRMAWATI SARI S.E.'],
            ['id_personil' => 53, 'id_bidang' => 2, 'nama_personil' => 'YADI SUPRIADI'],
            ['id_personil' => 54, 'id_bidang' => 2, 'nama_personil' => 'AYULIA NUR RACHMAWATI S.Sos'],
            ['id_personil' => 55, 'id_bidang' => 2, 'nama_personil' => 'DARUL TAUFIQ'],
            ['id_personil' => 56, 'id_bidang' => 2, 'nama_personil' => 'ALAN RIADI S.E. M. Si'],
            ['id_personil' => 57, 'id_bidang' => 2, 'nama_personil' => 'SRI PURWANINGSIH S.E.'],
            ['id_personil' => 58, 'id_bidang' => 2, 'nama_personil' => 'SITI RAHMAH S.E.'],
            ['id_personil' => 59, 'id_bidang' => 7, 'nama_personil' => 'Wawan Darmawan'],
            ['id_personil' => 60, 'id_bidang' => 7, 'nama_personil' => 'Suratman'],
            ['id_personil' => 61, 'id_bidang' => 7, 'nama_personil' => 'Adi Hermawan'],
            ['id_personil' => 62, 'id_bidang' => 7, 'nama_personil' => 'Otasi Carles Manalu'],
            ['id_personil' => 63, 'id_bidang' => 7, 'nama_personil' => 'Ahmad Imam Maulana'],
            ['id_personil' => 64, 'id_bidang' => 7, 'nama_personil' => 'Wahyu Ari Sucipto'],
            ['id_personil' => 65, 'id_bidang' => 7, 'nama_personil' => 'Atika Seknun'],
            ['id_personil' => 66, 'id_bidang' => 7, 'nama_personil' => 'Iyah Samsiyah'],
            ['id_personil' => 67, 'id_bidang' => 9, 'nama_personil' => 'Rini'],
            ['id_personil' => 68, 'id_bidang' => 8, 'nama_personil' => 'Erwin Yuniawan Kusuma'],
            ['id_personil' => 69, 'id_bidang' => 8, 'nama_personil' => 'Umar'],
            ['id_personil' => 70, 'id_bidang' => 8, 'nama_personil' => 'Haerudin'],
            ['id_personil' => 71, 'id_bidang' => 8, 'nama_personil' => 'Rizkia Safitri'],
            ['id_personil' => 72, 'id_bidang' => 8, 'nama_personil' => 'Syukur Makmun'],
            ['id_personil' => 73, 'id_bidang' => 8, 'nama_personil' => 'Beni Permana'],
            ['id_personil' => 74, 'id_bidang' => 8, 'nama_personil' => 'Basri Ramadan'],
            ['id_personil' => 75, 'id_bidang' => 6, 'nama_personil' => 'Dian Munandar'],
            ['id_personil' => 76, 'id_bidang' => 6, 'nama_personil' => 'Rachmat Ramadhan S. Ak'],
            ['id_personil' => 77, 'id_bidang' => 6, 'nama_personil' => 'Arfa Cesaria A. Md'],
            ['id_personil' => 78, 'id_bidang' => 6, 'nama_personil' => 'Nina Melinda S.Pd'],
            ['id_personil' => 79, 'id_bidang' => 6, 'nama_personil' => 'Mas Cecep Tino Noviandi A. Md'],
            ['id_personil' => 80, 'id_bidang' => 6, 'nama_personil' => 'Chintia Ainun Fadhilah S.M.'],
            ['id_personil' => 81, 'id_bidang' => 6, 'nama_personil' => 'Rizki Iriani A. Md'],
            ['id_personil' => 82, 'id_bidang' => 6, 'nama_personil' => 'Fariz Andifa S.Kom'],
            ['id_personil' => 83, 'id_bidang' => 6, 'nama_personil' => 'Imam Septiyansyah S. Kom'],
            ['id_personil' => 84, 'id_bidang' => 6, 'nama_personil' => 'Erma Diah Vitaloka S.T.'],
            ['id_personil' => 85, 'id_bidang' => 6, 'nama_personil' => 'Zaenudin S. Pd'],
            ['id_personil' => 86, 'id_bidang' => 6, 'nama_personil' => 'Neneng Muflihah S.E'],
            ['id_personil' => 87, 'id_bidang' => 6, 'nama_personil' => 'Dian Noviani S. IK'],
            ['id_personil' => 88, 'id_bidang' => 6, 'nama_personil' => 'Gema Alifa Eastiana S.I.Kom'],
            ['id_personil' => 89, 'id_bidang' => 6, 'nama_personil' => 'Muhamad Rafli S.E.'],
            ['id_personil' => 90, 'id_bidang' => 6, 'nama_personil' => 'Aulya Ardhi Fauqa S.H.'],
            ['id_personil' => 91, 'id_bidang' => 6, 'nama_personil' => 'Sandra Febri Ramdhiani S.H.'],
            ['id_personil' => 92, 'id_bidang' => 6, 'nama_personil' => 'Muhammad Fahmi S. Kom'],
            ['id_personil' => 93, 'id_bidang' => 6, 'nama_personil' => 'Devin Prasetia Bastian S.P.W.K'],
            ['id_personil' => 94, 'id_bidang' => 6, 'nama_personil' => 'Moch. Rizki Utama S.P.W.K'],
            ['id_personil' => 95, 'id_bidang' => 6, 'nama_personil' => 'Denna S. Dahmar Hidayati S. Ak'],
            ['id_personil' => 96, 'id_bidang' => 6, 'nama_personil' => 'Fahri Setya Gunawan'],
        ]);
    }
}