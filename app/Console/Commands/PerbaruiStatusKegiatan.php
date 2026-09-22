<?php

namespace App\Console\Commands;

use App\Models\Kegiatan;
use Illuminate\Console\Command;

class PerbaruiStatusKegiatan extends Command
{
    protected $signature = 'kegiatan:perbarui-status';

    protected $description = 'Tutup otomatis kegiatan yang lewat batas pendaftaran, dan tandai selesai kegiatan yang jadwalnya sudah lewat.';

    public function handle(): int
    {
        $today = now()->startOfDay();

        // 1) dibuka -> ditutup, kalau sudah lewat batas_pendaftaran
        $ditutup = Kegiatan::where('status', 'dibuka')
            ->whereNotNull('batas_pendaftaran')
            ->whereDate('batas_pendaftaran', '<', $today)
            ->update(['status' => 'ditutup']);

        // 2) dibuka/ditutup -> selesai, kalau jadwal kegiatannya sendiri sudah lewat
        //    (pakai tanggal_selesai kalau ada, kalau tidak pakai tanggal_mulai)
        $selesai = Kegiatan::whereIn('status', ['dibuka', 'ditutup'])
            ->where(function ($q) use ($today) {
                $q->where(function ($q2) use ($today) {
                    $q2->whereNotNull('tanggal_selesai')
                        ->whereDate('tanggal_selesai', '<', $today);
                })->orWhere(function ($q2) use ($today) {
                    $q2->whereNull('tanggal_selesai')
                        ->whereDate('tanggal_mulai', '<', $today);
                });
            })
            ->update(['status' => 'selesai']);

        $this->info("Kegiatan ditutup otomatis: {$ditutup}. Kegiatan ditandai selesai: {$selesai}.");

        return self::SUCCESS;
    }
}