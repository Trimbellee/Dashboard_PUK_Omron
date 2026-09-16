<?php

namespace App\Jobs;

use App\Models\Anggota;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BackfillPasswordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 600;

    public function handle(): void
    {
        $query = Anggota::where('is_active', 1)
            ->where(function ($q) {
                $q->whereNull('password')->orWhere('password', '');
            });

        $total = $query->count();

        if ($total === 0) {
            Log::info('BackfillPasswordJob dibatalkan: tidak ada anggota aktif tanpa password.');
            return;
        }

        Log::info('BackfillPasswordJob dimulai', ['total' => $total]);

        $query->chunkById(200, function ($chunk) {
            foreach ($chunk as $anggota) {
                $plain = strtoupper($anggota->nama_lengkap . '_' . $anggota->nik_karyawan);
                $anggota->update(['password' => Hash::make($plain)]);
            }
        });

        Log::info('BackfillPasswordJob selesai', ['total' => $total]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('BackfillPasswordJob gagal', [
            'message' => $exception->getMessage(),
        ]);
    }
}