<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KodeSurat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportKodeSuratCsv extends Command
{
    protected $signature = 'import:kodesurat {path}';
    protected $description = 'Import data Kode Surat dari CSV ke MySQL';

    public function handle()
    {
        $inputPath = $this->argument('path');

        $path = storage_path(
            str_replace(['storage/', 'storage\\'], '', $inputPath)
        );

        $this->info("Membaca file: {$path}");

        if (!file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return;
        }


        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->error("Gagal membuka file CSV");
            return;
        }

        DB::beginTransaction();

        try {
            $sample = fgets($handle);
            rewind($handle);

            // 🔍 AUTO DETECT DELIMITER
            $delimiters = [',', ';', "\t", '|'];
            $delimiter = ',';

            foreach ($delimiters as $d) {
                if (substr_count($sample, $d) > 1) {
                    $delimiter = $d;
                    break;
                }
            }

            $this->info("Delimiter terdeteksi: '{$delimiter}'");

            // SKIP HEADER
            fgetcsv($handle, 0, $delimiter);

            $count = 0;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count($row) < 4) continue;

                $no        = $this->clean($row[0] ?? '');
                $noSurat   = $this->clean($row[1] ?? '');
                $keterangan= $this->clean($row[2] ?? '');
                $tanggal   = $this->clean($row[3] ?? '');

                if (!$noSurat) continue;

                $parts = array_map('trim', explode('/', $noSurat));
                if (count($parts) !== 6) continue;

                $nomorUrut = (int) $parts[0];
                $jenis     = $parts[2];
                $bidang    = $parts[3];
                $bulanRom  = $parts[4];
                $tahun     = (int) $parts[5];

                $bulan = $this->romanToInt($bulanRom);

                try {
                    $createdAt = Carbon::createFromFormat(
                        'd/m/Y H:i:s',
                        $tanggal,
                        'Asia/Jakarta'
                    );
                } catch (\Exception $e) {
                    $createdAt = now();
                }

                if (KodeSurat::where('no_surat', $noSurat)->exists()) {
                    continue;
                }

                KodeSurat::create([
                    'nomor_urut'   => $nomorUrut,
                    'no_surat'     => $noSurat,
                    'jenis'        => $jenis,
                    'bidang'       => $bidang,
                    'bulan'        => $bulan,
                    'bulan_romawi' => $bulanRom,
                    'tahun'        => $tahun,
                    'keterangan'   => $keterangan,
                    'created_at'   => $createdAt,
                ]);

                $count++;
            }



            fclose($handle);
            DB::commit();

            $this->info("✅ Import selesai. {$count} data berhasil dimigrasikan.");

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("❌ Gagal import: " . $e->getMessage());
        }
    }

    private function romanToInt(string $roman): int
    {
        $map = [
            'XII'=>12,'XI'=>11,'X'=>10,'IX'=>9,
            'VIII'=>8,'VII'=>7,'VI'=>6,
            'V'=>5,'IV'=>4,'III'=>3,'II'=>2,'I'=>1
        ];

        return $map[$roman] ?? 0;
    }

    private function clean(string $value): string
    {
        // hapus BOM + whitespace aneh
        return trim(preg_replace('/^\xEF\xBB\xBF/', '', $value));
    }

}
