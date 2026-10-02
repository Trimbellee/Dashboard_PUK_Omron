<?php

namespace App\Console\Commands;

use App\Helpers\PdfCompressor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CompressExistingPdfs extends Command
{
    protected $signature = 'pdf:compress
                            {--dry-run : Hanya tampilkan perkiraan hasil, tidak mengubah file}
                            {--target=100 : Target ukuran per file (KB)}';

    protected $description = 'Kompres PDF proposal & laporan yang sudah ada (file asli di-backup dulu)';

    public function handle(): int
    {
        $target = max(1, (int) $this->option('target'));
        $dry    = (bool) $this->option('dry-run');

        $files = array_merge(
            Storage::files('file/proposal'),
            Storage::files('file/laporan')
        );

        if (!$files) {
            $this->warn('Tidak ada file PDF ditemukan.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[DRY-RUN] ' : '') . 'Memeriksa ' . count($files) . ' file, target ' . $target . ' KB');

        $done = $skipped = $failed = 0;
        $saved = 0;

        foreach ($files as $path) {
            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') {
                continue;
            }

            $old = Storage::size($path);

            // Sudah cukup kecil, lewati
            if ($old <= $target * 1024) {
                $skipped++;
                continue;
            }

            $new     = PdfCompressor::compress(Storage::path($path), $target);
            $newSize = strlen($new);

            // Hasil harus PDF valid dan benar-benar lebih kecil
            if (strncmp($new, '%PDF', 4) !== 0 || $newSize >= $old) {
                $this->line("  - lewati {$path} (tidak bisa diperkecil)");
                $failed++;
                continue;
            }

            $this->line(sprintf('  ✓ %s  %s → %s', $path, $this->kb($old), $this->kb($newSize)));

            if (!$dry) {
                // Backup file asli di storage/app/(private/)pdf-backup/...
                Storage::put('pdf-backup/' . $path, Storage::get($path));
                Storage::put($path, $new);
            }

            $saved += $old - $newSize;
            $done++;
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: %d dikompres, %d sudah kecil, %d tidak bisa diperkecil. Hemat %s.',
            $dry ? 'Perkiraan' : 'Selesai',
            $done,
            $skipped,
            $failed,
            $this->kb($saved)
        ));

        if (!$dry && $done > 0) {
            $this->line('File asli dicadangkan di folder pdf-backup (hapus manual kalau sudah yakin hasilnya baik).');
        }

        return self::SUCCESS;
    }

    private function kb(int $bytes): string
    {
        return number_format($bytes / 1024, 1) . ' KB';
    }
}