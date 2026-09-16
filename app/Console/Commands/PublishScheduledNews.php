<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\News;
use Illuminate\Console\Command;

/**
 * Mempublikasikan otomatis semua berita berstatus "scheduled" yang jadwal
 * publikasinya (scheduled_at) sudah tiba/lewat.
 *
 * Ini pasangan dari NewsAdminController::approve() — approve() sengaja TIDAK
 * langsung mempublikasikan berita yang scheduled_at-nya masih di masa depan,
 * hanya menandainya sudah disetujui Sekretaris (approved_by_sekretaris) dan
 * mempertahankan status 'scheduled'. Command inilah yang benar-benar
 * mengubah status jadi 'published' tepat pada waktunya, dengan dijalankan
 * berkala lewat scheduler Laravel.
 *
 * Memakai scope News::scheduled() (sudah ada di App\Models\News):
 *   status = 'scheduled' AND scheduled_at <= now()
 */
class PublishScheduledNews extends Command
{
    protected $signature = 'news:publish-scheduled';

    protected $description = 'Publikasikan otomatis berita berstatus "scheduled" yang jadwal publikasinya sudah tiba.';

    public function handle(): int
    {
        $dueNews = News::scheduled()->get();

        if ($dueNews->isEmpty()) {
            $this->info('Tidak ada berita terjadwal yang siap terbit saat ini.');
            return self::SUCCESS;
        }

        foreach ($dueNews as $berita) {
            $berita->update([
                'status' => 'published',
                'published_at' => now(),
            ]);

            ActivityLog::log($berita, 'published', 'Berita otomatis terbit sesuai jadwal publikasi');

            $this->line("Terbit: [{$berita->id}] {$berita->judul}");
        }

        $this->info($dueNews->count() . ' berita berhasil dipublikasikan otomatis.');

        return self::SUCCESS;
    }
}