<?php

namespace App\Console\Commands;

use App\Helpers\ImageWatermark;
use App\Models\News;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/**
 * Kompres gambar utama berita lama (PNG/JPG mentah atau webp yang terlalu besar)
 * menjadi webp ringan, supaya preview link di WhatsApp/Facebook muncul.
 *
 * Pemakaian:
 *   php artisan news:optimize-images --dry-run     (lihat dulu, tidak mengubah apa pun)
 *   php artisan news:optimize-images               (jalankan sungguhan)
 *   php artisan news:optimize-images --max-kb=250  (batas ukuran lain)
 *   php artisan news:optimize-images --no-watermark
 */
class OptimizeNewsImages extends Command
{
    protected $signature = 'news:optimize-images
                            {--dry-run : Hanya tampilkan daftar, tanpa mengubah file/database}
                            {--max-kb=300 : Gambar di atas ukuran ini (KB) akan dikompres}
                            {--no-watermark : Jangan beri watermark pada gambar non-webp}';

    protected $description = 'Kompres gambar utama berita lama menjadi webp ringan (untuk preview WhatsApp/OG)';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $maxBytes = (int) $this->option('max-kb') * 1024;
        $dry = (bool) $this->option('dry-run');

        $rows = News::whereNotNull('gambar')->where('gambar', '!=', '')->get();

        $done = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($rows as $news) {
            $old = $news->gambar;

            if (!$disk->exists($old)) {
                $this->warn("[#{$news->id}] file tidak ditemukan: {$old}");
                $failed++;
                continue;
            }

            $size = $disk->size($old);
            if ($size <= $maxBytes) {
                $skipped++;
                continue;
            }

            $isWebp = Str::endsWith(strtolower($old), '.webp');
            $this->line(sprintf('[#%d] %s (%s KB)', $news->id, $old, number_format($size / 1024, 0)));

            if ($dry) {
                $done++;
                continue;
            }

            try {
                $binary = $disk->get($old);
                $encoded = null;

                // Coba kualitas/lebar bertahap sampai ukurannya di bawah batas.
                foreach ([[1600, 80], [1280, 72], [1000, 65]] as [$maxWidth, $quality]) {
                    $image = Image::read($binary);
                    if ($image->width() > $maxWidth) {
                        $image->scaleDown(width: $maxWidth);
                    }
                    $encoded = (string) $image->toWebp($quality);
                    if (strlen($encoded) <= $maxBytes) {
                        break;
                    }
                }

                $slug = Str::slug(Str::limit($news->judul, 35, '')) ?: 'berita';
                $new = trim(dirname($old), './') . '/' . $slug . '-' . now()->format('Ymd_His') . '-' . Str::random(6) . '.webp';
                $new = ltrim($new, '/');

                $disk->put($new, $encoded);

                // Webp lama sudah pernah diberi watermark saat upload; hanya
                // gambar non-webp (upload lama) yang diberi watermark di sini.
                if (!$isWebp && !$this->option('no-watermark')) {
                    ImageWatermark::apply($new);
                }

                // Query builder: tidak mengubah updated_at berita.
                News::whereKey($news->id)->update(['gambar' => $new]);
                $disk->delete($old);

                $this->info(sprintf('   -> %s (%s KB)', $new, number_format($disk->size($new) / 1024, 0)));
                $done++;
            } catch (\Throwable $e) {
                $this->error("   gagal: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info(($dry ? '[DRY RUN] ' : '') . "Diproses: {$done}, dilewati (sudah ringan): {$skipped}, gagal: {$failed}");

        return self::SUCCESS;
    }
}