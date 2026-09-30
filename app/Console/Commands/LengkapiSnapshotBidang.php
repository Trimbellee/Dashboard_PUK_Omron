<?php
// app/Console/Commands/LengkapiSnapshotBidang.php
namespace App\Console\Commands;

use App\Models\LaporanBendahara;
use App\Models\LaporanBendaharaArsip;
use App\Services\Kas\KasKonfigurasi;
use Illuminate\Console\Command;

class LengkapiSnapshotBidang extends Command
{
    protected $signature = 'snapshot:lengkapi-bidang {--dry-run : Hanya tampilkan yang akan diubah, tanpa menyimpan}';

    protected $description = 'Menyuntikkan konfigurasi bidang (dari bidang_master saat ini) ke snapshot konfigurasi_kas_json laporan yang belum memilikinya';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $bidangSaatIni = KasKonfigurasi::master()['bidang'] ?? [];

        if (empty($bidangSaatIni)) {
            $this->error('bidang_master kosong. Jalankan seeder BidangMasterSeeder dulu sebelum command ini.');
            return self::FAILURE;
        }

        $this->info('Bidang yang akan disuntikkan: ' . count($bidangSaatIni) . ' baris.');

        $total = 0;
        foreach ([LaporanBendahara::class, LaporanBendaharaArsip::class] as $model) {
            $rows = $model::whereNotNull('konfigurasi_kas_json')->get();
            $diubah = 0;

            foreach ($rows as $row) {
                $konf = $row->konfigurasi_kas_json;

                if (!is_array($konf) || !empty($konf['bidang'])) {
                    continue; // sudah punya bidang, atau snapshot tidak valid sama sekali -> lewati
                }

                $konf['bidang'] = $bidangSaatIni;
                $diubah++;
                $total++;

                $label = $model === LaporanBendahara::class
                    ? "{$row->bulan}/{$row->tahun}"
                    : "{$row->bulan}/{$row->tahun} (arsip versi {$row->versi_ke})";

                $this->line(" - {$model}: {$label}" . ($dryRun ? ' [dry-run]' : ''));

                if (!$dryRun) {
                    $row->konfigurasi_kas_json = $konf;
                    $row->save();
                }
            }

            $this->info("{$model}: {$diubah} baris " . ($dryRun ? 'akan diubah' : 'diubah'));
        }

        $this->info($dryRun ? "Selesai (dry-run). Total {$total} baris akan diubah." : "Selesai. Total {$total} baris diubah.");

        return self::SUCCESS;
    }
}