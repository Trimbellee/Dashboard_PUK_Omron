<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FileManagerMakePrivate extends Command
{
    protected $signature = 'filemanager:make-private
                            {--dry-run : Hanya tampilkan apa yang akan dipindahkan}
                            {--keep : Jangan hapus salinan lama di disk public (hanya menyalin)}';

    protected $description = 'Pindahkan file File Manager dari disk public (bisa diakses lewat URL) ke disk privat';

    public function handle(): int
    {
        $dry     = (bool) $this->option('dry-run');
        $keep    = (bool) $this->option('keep');
        $public  = Storage::disk('public');
        $private = Storage::disk('local');

        $files = $public->allFiles('filemanager');

        if (!$files) {
            $this->info('Tidak ada file di disk public/filemanager. Tidak ada yang perlu dipindahkan.');
            return self::SUCCESS;
        }

        $this->info(($dry ? '[DRY-RUN] ' : '') . 'Memeriksa ' . count($files) . ' file…');

        $moved = $skipped = $failed = 0;
        $bytes = 0;

        foreach ($files as $path) {
            $size = $public->size($path);

            // sudah ada di disk privat
            if ($private->exists($path)) {
                if ($private->size($path) === $size) {
                    if (!$dry && !$keep) {
                        $public->delete($path);
                    }
                    $skipped++;
                } else {
                    $this->warn("  ! {$path}: sudah ada di disk privat dengan ukuran berbeda, dilewati");
                    $failed++;
                }
                continue;
            }

            $this->line(sprintf('  → %s (%s KB)', $path, number_format($size / 1024, 1)));

            if ($dry) {
                $moved++;
                $bytes += $size;
                continue;
            }

            $stream = $public->readStream($path);

            if (!$stream || !$private->writeStream($path, $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $this->error("  ✕ gagal menyalin {$path}");
                $failed++;
                continue;
            }

            if (is_resource($stream)) {
                fclose($stream);
            }

            // verifikasi sebelum menghapus salinan lama
            if ($private->size($path) !== $size) {
                $private->delete($path);
                $this->error("  ✕ ukuran tidak cocok setelah disalin: {$path}");
                $failed++;
                continue;
            }

            if (!$keep) {
                $public->delete($path);
            }

            $moved++;
            $bytes += $size;
        }

        if (!$dry && !$keep && !$failed && !$public->allFiles('filemanager')) {
            $public->deleteDirectory('filemanager');
        }

        $this->newLine();
        $this->info(sprintf(
            '%s: %d dipindahkan (%s MB), %d sudah ada di privat, %d gagal/dilewati.',
            $dry ? 'Perkiraan' : 'Selesai',
            $moved,
            number_format($bytes / 1048576, 1),
            $skipped,
            $failed
        ));

        if (!$dry && $failed === 0) {
            $this->line('Semua file kini privat. URL lama /storage/filemanager/... tidak lagi bisa dibuka.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}