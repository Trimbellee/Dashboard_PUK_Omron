<?php
// tests/Unit/KasCalculatorTest.php
namespace Tests\Unit;

use App\Services\Kas\Formula;
use App\Services\Kas\FormulaException;
use App\Services\Kas\KasCalculator;
use Database\Seeders\KasMasterSeeder;
use PHPUnit\Framework\TestCase;

class KasCalculatorTest extends TestCase
{
    private function kalkulator(): KasCalculator
    {
        $params = [];
        foreach (KasMasterSeeder::parameter() as $kode => [, $nilai]) {
            $params[$kode] = $nilai;
        }
        $kas = array_map(fn ($k) => $k + ['aktif' => true], KasMasterSeeder::kas());

        return new KasCalculator($kas, $params);
    }

    /** Port apa adanya dari recalc() di blade (rumus lama). */
    private function lama(array $in): array
    {
        $cos = $in['cos'];
        $sum = $in['sumbangan'];
        $b   = $in['bidang'];

        $debitCosDpp   = $in['debit_manual']['cos_dpp'];
        $debitTransfer = $in['debit_manual']['biaya_transfer'];

        $debit = [
            'dana_operasional'     => floor(($cos / 1.5) - ($debitCosDpp + $debitTransfer)) + $sum['dana_operasional'],
            'dana_perjuangan'      => floor($cos * 0.5 / 1.5 * 0.2 / 0.5) + $sum['dana_perjuangan'],
            'dana_sosial'          => floor($cos * 0.5 / 1.5 * 0.05 / 0.5) + $sum['dana_sosial'],
            'dana_permusyawaratan' => floor($cos * 0.5 / 1.5 * 0.2 / 0.5) + $sum['dana_permusyawaratan'],
            'dana_sosial_politik'  => floor($cos * 0.5 / 1.5 * 0.05 / 0.5) + $sum['dana_sosial_politik'],
            'bunga_giro'           => $in['debit_manual']['bunga_giro'],
            'bunga_deposito'       => $in['debit_manual']['bunga_deposito'],
            'cos_dpp'              => $debitCosDpp,
            'biaya_transfer'       => $debitTransfer,
        ];

        $kredit = [
            'dana_operasional' => $b['SEKRETARIS'] + $in['insentif'] + $b['BIDANG_1'] + $b['BIDANG_2']
                + $b['BIDANG_3'] + $b['BIDANG_4'] + $b['BIDANG_5'] + $b['BIDANG_6']
                + $b['GM'] + $b['KORLAP'] + $in['dana_lain'],
            'dana_perjuangan'      => $b['DANA_PERJUANGAN'],
            'dana_sosial'          => $b['DANA_SOSIAL'],
            'dana_permusyawaratan' => $b['DANA_PERMUSYAWARATAN'],
            'dana_sosial_politik'  => $b['DANA_SOSIAL_POLITIK'],
            'bunga_giro'           => $b['BUNGA_GIRO'] + $in['pajak']['bunga_giro'],
            'bunga_deposito'       => $b['BUNGA_DEPOSITO'],
            'cos_dpp'              => $in['kredit_manual']['cos_dpp'],
            'biaya_transfer'       => $in['kredit_manual']['biaya_transfer'],
        ];

        $hasil = [];
        foreach ($debit as $kode => $d) {
            $saldo = in_array($kode, ['cos_dpp', 'biaya_transfer']) ? 0 : $in['saldo_awal'][$kode];
            $hasil[$kode] = [
                'saldo_awal' => (int) $saldo,
                'debit'      => (int) floor($d),
                'kredit'     => (int) floor($kredit[$kode]),
                'total'      => (int) floor($saldo + $d - $kredit[$kode]),
            ];
        }
        return $hasil;
    }

    private function inputAcak(): array
    {
        $r = fn () => mt_rand(0, 300_000_000);

        return [
            'cos' => mt_rand(0, 900_000_000),
            'saldo_awal' => array_fill_keys(
                ['dana_operasional', 'dana_perjuangan', 'dana_sosial', 'dana_permusyawaratan',
                 'dana_sosial_politik', 'bunga_giro', 'bunga_deposito'], $r()),
            'debit_manual' => [
                'bunga_giro' => $r(), 'bunga_deposito' => $r(),
                'cos_dpp' => mt_rand(0, 5_000_000), 'biaya_transfer' => mt_rand(0, 1_000_000),
            ],
            'kredit_manual' => ['cos_dpp' => $r(), 'biaya_transfer' => mt_rand(0, 1_000_000)],
            'bidang' => [
                'SEKRETARIS' => $r(), 'BIDANG_1' => $r(), 'BIDANG_2' => $r(), 'BIDANG_3' => $r(),
                'BIDANG_4' => $r(), 'BIDANG_5' => $r(), 'BIDANG_6' => $r(), 'GM' => $r(), 'KORLAP' => $r(),
                'DANA_PERJUANGAN' => $r(), 'DANA_SOSIAL' => $r(), 'DANA_PERMUSYAWARATAN' => $r(),
                'DANA_SOSIAL_POLITIK' => $r(), 'BUNGA_GIRO' => $r(), 'BUNGA_DEPOSITO' => $r(),
            ],
            'insentif' => mt_rand(0, 15_000_000),
            'dana_lain' => mt_rand(0, 10_000_000),
            'pajak' => ['bunga_giro' => mt_rand(0, 5_000_000)],
            'sumbangan' => [
                'dana_operasional' => mt_rand(0, 5_000_000), 'dana_perjuangan' => mt_rand(0, 5_000_000),
                'dana_sosial' => mt_rand(0, 5_000_000), 'dana_permusyawaratan' => mt_rand(0, 5_000_000),
                'dana_sosial_politik' => mt_rand(0, 5_000_000),
            ],
        ];
    }

    public function test_hasil_sama_dengan_rumus_lama(): void
    {
        mt_srand(2026);
        $kalk = $this->kalkulator();

        for ($i = 0; $i < 500; $i++) {
            $in = $this->inputAcak();
            $lama = $this->lama($in);
            $baru = $kalk->hitung($in);

            foreach ($baru['rows'] as $row) {
                foreach (['saldo_awal', 'debit', 'kredit', 'total'] as $f) {
                    $this->assertSame(
                        $lama[$row['kode']][$f], $row[$f],
                        "Beda di {$row['kode']}.{$f} (COS={$in['cos']})"
                    );
                }
            }
        }
    }

    public function test_grand_total_konsisten(): void
    {
        mt_srand(7);
        $hasil = $this->kalkulator()->hitung($this->inputAcak());

        $this->assertSame(
            $hasil['grand']['saldo_awal'] + $hasil['grand']['debit'] - $hasil['grand']['kredit'],
            $hasil['grand']['total']
        );
    }

    public function test_siklus_ditolak(): void
    {
        $this->expectException(FormulaException::class);

        (new KasCalculator([
            ['kode' => 'a', 'nama' => 'A', 'aktif' => true, 'debit_mode' => 'rumus', 'debit_rumus' => 'DEBIT.b'],
            ['kode' => 'b', 'nama' => 'B', 'aktif' => true, 'debit_mode' => 'rumus', 'debit_rumus' => 'DEBIT.a'],
        ], []))->validasi();
    }

    public function test_variabel_tak_dikenal_ditolak(): void
    {
        $this->expectException(FormulaException::class);

        (new KasCalculator([
            ['kode' => 'a', 'nama' => 'A', 'aktif' => true, 'debit_mode' => 'rumus', 'debit_rumus' => 'COS * XYZ'],
        ], []))->validasi();
    }

    public function test_karakter_berbahaya_ditolak(): void
    {
        $this->expectException(FormulaException::class);
        Formula::parse('system("ls")');
    }

    public function test_operator_dan_prioritas(): void
    {
        $f = Formula::parse('floor(10 / 3) + 2 * (1 + 2) - -1');
        $this->assertSame(10.0, $f->evaluate(fn () => 0)); // 3 + 6 + 1
    }
}