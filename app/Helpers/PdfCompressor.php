<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Helper Ghostscript untuk mengompres PDF.
 * Aman: apa pun yang gagal tidak melempar exception
 * (compress() mengembalikan file asli).
 *
 * .env:  GS_BINARY=gs (Linux)  |  GS_BINARY=gswin64c (Windows)
 */
class PdfCompressor
{
    private const PRESETS = ['/ebook', '/screen'];

    /** Kompres file PDF di path tertentu, return isi PDF (binary string). */
    public static function compress(string $inputPath, int $targetKb = 100): string
    {
        $temps    = [];
        $bestPath = $inputPath;
        $bestSize = filesize($inputPath);

        try {
            foreach (self::PRESETS as $preset) {
                if ($bestSize <= $targetKb * 1024) {
                    break;
                }

                $tmp     = tempnam(self::tempDir(), 'pdfc_');
                $temps[] = $tmp;

                $ok = self::run([
                    '-sDEVICE=pdfwrite',
                    '-dCompatibilityLevel=1.4',
                    "-dPDFSETTINGS={$preset}",
                    '-dDetectDuplicateImages=true',
                    '-dCompressFonts=true',
                    '-dSubsetFonts=true',
                    '-sOutputFile=' . $tmp,
                    $inputPath,
                ], 60);

                if ($ok) {
                    $size = filesize($tmp);
                    if ($size > 0 && $size < $bestSize) {
                        $bestPath = $tmp;
                        $bestSize = $size;
                    }
                }
            }

            return file_get_contents($bestPath);
        } catch (\Throwable $e) {
            Log::warning('[PdfCompressor] ' . $e->getMessage());
            return file_get_contents($inputPath);
        } finally {
            foreach ($temps as $t) {
                @unlink($t);
            }
        }
    }

    /** Kompres PDF yang masih berupa string (mis. hasil $pdf->output() DomPDF). */
    public static function compressContent(string $pdfBinary, int $targetKb = 100): string
    {
        $tmpIn = null;

        try {
            $tmpIn = tempnam(self::tempDir(), 'pdfi_');
            file_put_contents($tmpIn, $pdfBinary);

            return self::compress($tmpIn, $targetKb);
        } catch (\Throwable $e) {
            Log::warning('[PdfCompressor] ' . $e->getMessage());
            return $pdfBinary;
        } finally {
            if ($tmpIn) {
                @unlink($tmpIn);
            }
        }
    }

    /**
     * Folder temp khusus di storage/app/pdf-tmp.
     * Di Windows, php artisan serve sering tidak mewariskan TEMP/TMP
     * sehingga Ghostscript error "Could not open temporary file".
     */
    private static function tempDir(): string
    {
        $dir = storage_path('app/pdf-tmp');

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return (is_dir($dir) && is_writable($dir)) ? $dir : sys_get_temp_dir();
    }

    /** Jalankan Ghostscript dengan argumen tambahan. */
    private static function run(array $args, int $timeout): bool
    {
        try {
            $dir = self::tempDir();

            $process = new Process(array_merge(
                [env('GS_BINARY', 'gs'), '-dNOPAUSE', '-dQUIET', '-dBATCH', '-dSAFER'],
                $args
            ));
            $process->setEnv(['TEMP' => $dir, 'TMP' => $dir, 'TMPDIR' => $dir]);
            $process->setTimeout($timeout);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::warning('[PdfCompressor] Ghostscript gagal: ' . $process->getErrorOutput());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('[PdfCompressor] Ghostscript tidak bisa dijalankan: ' . $e->getMessage());
            return false;
        }
    }
}