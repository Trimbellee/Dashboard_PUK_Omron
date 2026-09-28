<?php
// app/../database/seeders/KasMasterSeeder.php
namespace Database\Seeders;

use App\Models\KasMaster;
use App\Models\LaporanParameter;
use Illuminate\Database\Seeder;

class KasMasterSeeder extends Seeder
{
    public static function parameter(): array
    {
        return [
            'PEMBAGI_COS'           => ['Pembagi COS', 1.5],
            'PORSI_PERJUANGAN'      => ['Porsi Dana Perjuangan', 0.20],
            'PORSI_SOSIAL'          => ['Porsi Dana Sosial', 0.05],
            'PORSI_PERMUSYAWARATAN' => ['Porsi Dana Permusyawaratan', 0.20],
            'PORSI_SOSPOL'          => ['Porsi Dana Sosial Politik', 0.05],
        ];
    }

    public static function kas(): array
    {
        $kreditOperasional =
            'BIDANG.SEKRETARIS + INSENTIF + BIDANG.BIDANG_1 + BIDANG.BIDANG_2 + BIDANG.BIDANG_3 '
          . '+ BIDANG.BIDANG_4 + BIDANG.BIDANG_5 + BIDANG.BIDANG_6 + BIDANG.GM + BIDANG.KORLAP + DANA_LAIN';

        $alokasi = fn(string $kode, string $nama, string $porsi) => [
            'kode' => $kode, 'nama' => $nama,
            'saldo_awal_mode' => 'carry_over',
            'debit_mode' => 'rumus', 'debit_rumus' => "floor(COS / PEMBAGI_COS * {$porsi})",
            'kredit_mode' => 'rumus', 'kredit_rumus' => 'BIDANG.' . strtoupper($kode),
            'menerima_sumbangan' => true, 'tampil_rincian' => true, 'nama_bidang' => $nama,
        ];

        return [
            [
                'kode' => 'dana_operasional', 'nama' => 'Dana Operasional',
                'saldo_awal_mode' => 'carry_over',
                'debit_mode' => 'rumus',
                'debit_rumus' => 'floor(COS / PEMBAGI_COS - (DEBIT.COS_DPP + DEBIT.BIAYA_TRANSFER))',
                'kredit_mode' => 'rumus', 'kredit_rumus' => $kreditOperasional,
                'menerima_sumbangan' => true,
            ],
            $alokasi('dana_perjuangan', 'Dana Perjuangan', 'PORSI_PERJUANGAN'),
            $alokasi('dana_sosial', 'Dana Sosial', 'PORSI_SOSIAL'),
            $alokasi('dana_permusyawaratan', 'Dana Permusyawaratan', 'PORSI_PERMUSYAWARATAN'),
            $alokasi('dana_sosial_politik', 'Dana Sosial Politik', 'PORSI_SOSPOL'),
            [
                'kode' => 'bunga_giro', 'nama' => 'Bunga Giro',
                'saldo_awal_mode' => 'carry_over',
                'debit_mode' => 'manual',
                'kredit_mode' => 'rumus', 'kredit_rumus' => 'BIDANG.BUNGA_GIRO + PAJAK.BUNGA_GIRO',
                'tampil_rincian' => true, 'nama_bidang' => 'Bunga Giro',
                'punya_pajak' => true, 'label_pajak' => 'Pajak Bank', 'tujuan_pajak' => 'Bank',
            ],
            [
                'kode' => 'bunga_deposito', 'nama' => 'Bunga Deposito',
                'saldo_awal_mode' => 'carry_over',
                'debit_mode' => 'manual',
                'kredit_mode' => 'rumus', 'kredit_rumus' => 'BIDANG.BUNGA_DEPOSITO',
                'tampil_rincian' => true, 'nama_bidang' => 'Bunga Deposito',
            ],
            [
                'kode' => 'cos_dpp', 'nama' => 'COS DPP',
                'saldo_awal_mode' => 'nol', 'debit_mode' => 'manual', 'kredit_mode' => 'manual',
            ],
            [
                'kode' => 'biaya_transfer', 'nama' => 'Biaya Transfer',
                'saldo_awal_mode' => 'nol', 'debit_mode' => 'manual', 'kredit_mode' => 'manual',
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::parameter() as $kode => [$nama, $nilai]) {
            LaporanParameter::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'nilai' => $nilai]);
        }

        foreach (self::kas() as $i => $row) {
            KasMaster::updateOrCreate(['kode' => $row['kode']], $row + ['urutan' => $i + 1, 'aktif' => true]);
        }
    }
}