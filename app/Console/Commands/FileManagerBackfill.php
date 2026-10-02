<?php

namespace App\Console\Commands;

use App\Helpers\PdfCompressor;
use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FileManagerBackfill extends Command
{
    protected $signature = 'filemanager:backfill
                            {--regen : Buat ulang semua thumbnail, bukan hanya yang belum ada}';

    protected $description = 'Isi kolom size dan buat thumbnail PDF untuk file File Manager yang sudah ada';

    public function handle(): int
    {
        $disk  = Storage::disk('public');
        $regen = (bool) $this->option('regen');
        $n = $thumbs = $missing = 0;

        File::withTrashed()
            ->where('type', 'file')
            ->whereNotNull('path')
            ->chunkById(100, function ($chunk) use ($disk, $regen, &$n, &$thumbs, &$missing) {
                foreach ($chunk as $f) {
                    if (!$disk->exists($f->path)) {
                        $missing++;
                        continue;
                    }

                    $updates = [];
                    $size    = $disk->size($f->path);

                    if ((int) $f->size !== $size) {
                        $updates['size'] = $size;
                    }

                    $isPdf = strtolower(pathinfo($f->name, PATHINFO_EXTENSION)) === 'pdf';

                    if ($isPdf && ($regen || !$f->thumbnail || !$disk->exists($f->thumbnail))) {
                        $jpg = PdfCompressor::thumbnail($disk->path($f->path));
                        if ($jpg) {
                            $thumb = 'filemanager/thumbs/' . pathinfo($f->path, PATHINFO_FILENAME) . '.jpg';
                            $disk->put($thumb, $jpg);
                            $updates['thumbnail'] = $thumb;
                            $thumbs++;
                        }
                    }

                    if ($updates) {
                        $f->timestamps = false; // jangan ubah "tanggal diubah"
                        $f->forceFill($updates)->save();
                        $n++;
                    }
                }
            });

        $this->info("Selesai: {$n} baris diperbarui, {$thumbs} thumbnail dibuat, {$missing} file fisik tidak ditemukan.");

        return self::SUCCESS;
    }
}