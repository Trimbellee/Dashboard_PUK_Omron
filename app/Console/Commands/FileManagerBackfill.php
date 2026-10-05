<?php

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FileManagerBackfill extends Command
{
    protected $signature = 'filemanager:backfill';

    protected $description = 'Isi/sinkronkan kolom size untuk file File Manager yang sudah ada';

    public function handle(): int
    {
        $private = Storage::disk('local');
        $public  = Storage::disk('public');
        $n = $missing = 0;

        File::withTrashed()
            ->where('type', 'file')
            ->whereNotNull('path')
            ->chunkById(100, function ($chunk) use ($private, $public, &$n, &$missing) {
                foreach ($chunk as $f) {
                    // file baru ada di disk privat; file lama (belum dimigrasi) masih di public
                    $disk = $private->exists($f->path) ? $private : ($public->exists($f->path) ? $public : null);

                    if (!$disk) {
                        $missing++;
                        continue;
                    }

                    $size = $disk->size($f->path);

                    if ((int) $f->size !== $size) {
                        $f->timestamps = false; // jangan ubah "tanggal diubah"
                        $f->forceFill(['size' => $size])->save();
                        $n++;
                    }
                }
            });

        $this->info("Selesai: {$n} baris diperbarui, {$missing} file fisik tidak ditemukan.");

        return self::SUCCESS;
    }
}