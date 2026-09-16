<?php
namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\AnggotaOtpResetMail;
use App\Models\Anggota;
use App\Jobs\SendAnggotaOtpJob;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class AnggotaPasswordResetController extends Controller
{
    const OTP_EXPIRY_MINUTES = 10;
    const MAX_ATTEMPTS = 5;
    const RESEND_COOLDOWN_SECONDS = 60;

    /* ========================================================
     | STEP 1 — Verifikasi NIK + Email
     ======================================================== */

    public function showIdentityForm(): View|RedirectResponse
    {
        if (session()->has('anggota')) {
            return redirect()->route('landing.index');
        }
        return view('landing-page.auth.forgot-password');
    }

    public function verifyIdentity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik_karyawan' => ['required', 'string', 'max:8'],
            'email'        => ['required', 'email'],
        ]);

        $throttleKey = 'reset-identity:' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->with('error', 'Terlalu banyak percobaan. Coba lagi dalam beberapa saat.');
        }
        RateLimiter::hit($throttleKey, 300);

        $anggota = Anggota::where('nik_karyawan', $validated['nik_karyawan'])
            ->where('email', $validated['email'])
            ->where('is_active', true)
            ->first();

        if (! $anggota) {
            return back()
                ->withInput($request->only('nik_karyawan', 'email'))
                ->with('error', 'NIK Karyawan dan Email tidak cocok dengan data anggota kami. Silakan hubungi Bidang 2 jika Anda merasa data ini sudah benar.');
        }

        $this->generateAndSendOtp($anggota);

        session([
            'reset_anggota_id'   => $anggota->id,
            'reset_email_masked' => $this->maskEmail($anggota->email),
        ]);

        return redirect()->route('anggota.password.otp')
            ->with('success', 'Kode OTP telah dikirim ke email Anda.');
    }

    /* ========================================================
     | STEP 2 — Verifikasi OTP
     ======================================================== */

    public function showOtpForm(): View|RedirectResponse
    {
        if (! session()->has('reset_anggota_id')) {
            return redirect()->route('anggota.password.request');
        }

        return view('landing-page.auth.verify-otp', [
            'emailMasked' => session('reset_email_masked'),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $anggotaId = session('reset_anggota_id');
        if (! $anggotaId) {
            return redirect()->route('anggota.password.request');
        }

        $request->validate(['token' => ['required', 'digits:6']]);

        $record = DB::table('anggota_password_resets')->where('anggota_id', $anggotaId)->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast()) {
            return back()->with('error', 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.');
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            DB::table('anggota_password_resets')->where('anggota_id', $anggotaId)->delete();
            session()->forget(['reset_anggota_id', 'reset_email_masked']);
            return redirect()->route('anggota.password.request')
                ->with('error', 'Terlalu banyak percobaan salah. Silakan ulangi dari awal.');
        }

        if (! Hash::check($request->token, $record->token)) {
            DB::table('anggota_password_resets')->where('anggota_id', $anggotaId)->increment('attempts');
            return back()->with('error', 'Kode OTP salah. Sisa percobaan: ' . (self::MAX_ATTEMPTS - $record->attempts - 1));
        }

        DB::table('anggota_password_resets')
            ->where('anggota_id', $anggotaId)
            ->update(['verified_at' => Carbon::now()]);

        session(['reset_otp_verified' => true]);

        return redirect()->route('anggota.password.reset');
    }

    public function resendOtp(): RedirectResponse
    {
        $anggotaId = session('reset_anggota_id');
        if (! $anggotaId) {
            return redirect()->route('anggota.password.request');
        }

        $throttleKey = 'reset-resend:' . $anggotaId;
        if (RateLimiter::tooManyAttempts($throttleKey, 1)) {
            return back()->with('error', 'Tunggu sebentar sebelum meminta kode baru.');
        }
        RateLimiter::hit($throttleKey, self::RESEND_COOLDOWN_SECONDS);

        $anggota = Anggota::find($anggotaId);
        if (! $anggota) {
            return redirect()->route('anggota.password.request');
        }

        $this->generateAndSendOtp($anggota);

        return back()->with('success', 'Kode OTP baru telah dikirim.');
    }

    /* ========================================================
     | STEP 3 — Set Password Baru
     ======================================================== */

    public function showResetForm(): View|RedirectResponse
    {
        if (! $this->hasVerifiedOtp()) {
            return redirect()->route('anggota.password.request');
        }
        return view('landing-page.auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $anggotaId = session('reset_anggota_id');
        if (! $this->hasVerifiedOtp()) {
            return redirect()->route('anggota.password.request');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $anggota = Anggota::find($anggotaId);
        if (! $anggota) {
            return redirect()->route('anggota.password.request')->with('error', 'Akun tidak ditemukan.');
        }

        $anggota->update(['password' => Hash::make($request->password)]);

        DB::table('anggota_password_resets')->where('anggota_id', $anggotaId)->delete();
        session()->forget(['reset_anggota_id', 'reset_email_masked', 'reset_otp_verified']);

        return redirect()->route('anggota.login')
            ->with('success', 'Password berhasil diubah. Silakan login dengan password baru.');
    }

    /* ========================================================
     | Helper
     ======================================================== */

    private function generateAndSendOtp(Anggota $anggota): void
    {
        $kode = (string) random_int(100000, 999999);

        DB::table('anggota_password_resets')->updateOrInsert(
            ['anggota_id' => $anggota->id],
            [
                'token'       => Hash::make($kode),
                'attempts'    => 0,
                'verified_at' => null,
                'expires_at'  => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
                'created_at'  => Carbon::now(),
            ]
        );

        SendAnggotaOtpJob::dispatch($anggota->id, $anggota->nama_lengkap, $anggota->email, $kode);
    }

    private function hasVerifiedOtp(): bool
    {
        $anggotaId = session('reset_anggota_id');
        if (! session('reset_otp_verified') || ! $anggotaId) {
            return false;
        }

        // Guard tambahan di level DB, bukan cuma session
        $record = DB::table('anggota_password_resets')->where('anggota_id', $anggotaId)->first();
        return $record && $record->verified_at !== null;
    }

    private function maskEmail(string $email): string
    {
        $atPos = strrpos($email, '@');
        if ($atPos === false) {
            return $email; // fallback safety, seharusnya tidak terjadi setelah validasi email
        }

        $local  = substr($email, 0, $atPos);
        $domain = substr($email, $atPos + 1);

        $length = strlen($local);

        // Tampilkan maks 2 karakter depan (1 karakter kalau local part sangat pendek)
        $visibleCount = $length <= 3 ? 1 : 2;
        $visible = substr($local, 0, $visibleCount);

        // Selalu pakai jumlah asterisk tetap (3), tidak proporsional ke panjang asli,
        // supaya panjang local part sungguhan tidak ikut bocor.
        $masked = str_repeat('*', 3);

        return $visible . $masked . '@' . $domain;
    }
}