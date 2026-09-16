<?php

namespace App\Console\Commands;

use App\Models\Anggota;
use Illuminate\Console\Command;

class SyncJabatanPuk extends Command
{
    protected $signature = 'anggota:sync-jabatan-puk';
    protected $description = 'Sinkronisasi jabatan_puk untuk anggota aktif berdasarkan struktur organisasi';

    public function handle()
    {
        $this->info('🔄 Mengupdate jabatan_puk untuk anggota aktif...');

        $anggota = Anggota::where('is_active', true)->get();
        $total = count($anggota);

        if ($total === 0) {
            $this->warn('Tidak ada anggota aktif yang ditemukan.');
            return;
        }

        $bar = $this->output->createProgressBar($total);

        foreach ($anggota as $a) {
            $a->syncJabatanPuk();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $stats = Anggota::where('is_active', true)
            ->select('jabatan_puk')
            ->selectRaw('count(*) as total')
            ->groupBy('jabatan_puk')
            ->orderBy('total', 'desc')
            ->get();

        $this->info('✅ Selesai! Statistik jabatan_puk (aktif):');
        $this->table(['Jabatan PUK', 'Jumlah'], $stats->map(fn($s) => [$s->jabatan_puk, $s->total]));

        $this->info('Total ' . $total . ' anggota aktif diperbarui.');
    }
}