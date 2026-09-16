<?php

namespace App\Jobs;

use App\Mail\AnggotaOtpResetMail;
use App\Services\BrevoMailService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SendAnggotaOtpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $anggotaId,
        public string $namaLengkap,
        public string $email,
        public string $kode,
    ) {}

    public function handle(): void
    {
        $record = DB::table('anggota_password_resets')
            ->where('anggota_id', $this->anggotaId)
            ->first();

        // Kalau baris sudah tidak ada (mis. sudah dipakai reset password / dihapus), skip.
        if (! $record) {
            Log::info('SendAnggotaOtpJob dibatalkan: record tidak ditemukan', [
                'anggota_id' => $this->anggotaId,
            ]);
            return;
        }

        // Kalau sudah expired, skip — tidak ada gunanya kirim OTP basi.
        if (! $record->expires_at || Carbon::parse($record->expires_at)->isPast()) {
            Log::info('SendAnggotaOtpJob dibatalkan: OTP sudah kedaluwarsa', [
                'anggota_id' => $this->anggotaId,
            ]);
            return;
        }

        // Kalau token di DB sudah bukan kode ini lagi (sudah ditimpa OTP yang lebih baru), skip.
        if (! Hash::check($this->kode, $record->token)) {
            Log::info('SendAnggotaOtpJob dibatalkan: OTP sudah ditimpa kode baru', [
                'anggota_id' => $this->anggotaId,
            ]);
            return;
        }

        BrevoMailService::sendMailable(
            $this->email,
            $this->namaLengkap,
            new AnggotaOtpResetMail($this->namaLengkap, $this->kode)
        );
    }
}