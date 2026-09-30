<?php
// database/seeders/BidangMasterSeeder.php
namespace Database\Seeders;

use App\Models\BidangMaster;
use Illuminate\Database\Seeder;

class BidangMasterSeeder extends Seeder
{
    public static function data(): array
    {
        $operasional = [
            ['ketua', 'Ketua'], ['sekretaris', 'Sekretaris'], ['bendahara', 'Bendahara'],
            ['bidang_1', 'Bidang 1'], ['bidang_2', 'Bidang 2'], ['bidang_3', 'Bidang 3'],
            ['bidang_4', 'Bidang 4'], ['bidang_5', 'Bidang 5'], ['bidang_6', 'Bidang 6'],
            ['gm', 'GM'], ['korlap', 'KorLap'],
        ];

        $rows = [];
        $urutan = 1;

        foreach ($operasional as [$kode, $nama]) {
            $rows[] = [
                'kode' => $kode, 'nama' => $nama, 'urutan' => $urutan++,
                'kas_tujuan' => 'dana_operasional',
                'punya_insentif' => $kode === 'sekretaris',
            ];
        }

        // satu bidang per kas lain, kas_tujuan = kode-nya sendiri di kas_master
        $satuSatu = [
            ['dana_sosial', 'Dana Sosial'],
            ['dana_perjuangan', 'Dana Perjuangan'],
            ['dana_permusyawaratan', 'Dana Permusyawaratan'],
            ['dana_sosial_politik', 'Dana Sosial Politik'],
            ['bunga_giro', 'Bunga Giro'],
            ['bunga_deposito', 'Bunga Deposito'],
        ];

        foreach ($satuSatu as [$kode, $nama]) {
            $rows[] = [
                'kode' => $kode, 'nama' => $nama, 'urutan' => $urutan++,
                'kas_tujuan' => $kode,       // kebetulan kode bidang = kode kas di sini
                'punya_insentif' => false,
            ];
        }

        return $rows;
    }

    public function run(): void
    {
        foreach (self::data() as $row) {
            BidangMaster::updateOrCreate(['kode' => $row['kode']], $row + ['aktif' => true]);
        }
    }
}